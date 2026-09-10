import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Form, Nav } from 'react-bootstrap';
import client from './api/client';
import useDomainTranslator from './useDomainTranslator';
import useContentLocale from './useContentLocale';

// Both consumed from other plugins' Module Federation remotes - lazy since
// resolving a remote container is inherently async.
const RichTextEditor = lazy(() => import('adm_host/RichTextEditor'));
const BuilderCanvas = lazy(() => import('page_builder/BuilderCanvas'));

/**
 * Admin edit form for the homepage content (step 21) - a singleton, so
 * unlike PageForm/PortfolioItemForm/NewsArticleForm there is no list, no
 * title, no status: just the content editor and a save button, always
 * PATCHing the same `/admin/homepage` resource (auto-created on first read).
 */
export default function HomepageForm() {
    const { t } = useDomainTranslator('homepage');
    // Whichever editor is active, this holds its native value: builder JSON
    // when the builder is active, plain HTML otherwise.
    const [contentValue, setContentValue] = useState('');
    // The singleton row's own id - the homepage always exists once loaded
    // (auto-created server-side on first read), so this is only ever null
    // while still loading.
    const [contentId, setContentId] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    // Transient success feedback: unlike the other content plugins' forms,
    // saving here never navigates away (no list to return to), so the form
    // needs its own visible confirmation.
    const [saved, setSaved] = useState(false);
    // null while still checking, so the form doesn't flash one editor then
    // swap to the other once the plugin list has loaded.
    const [builderActive, setBuilderActive] = useState(null);

    // Content-language switcher (step 58 follow-up).
    const { activeLangs, editingLocale, setEditingLocale, fieldValue, setField, saveAllTranslations } =
        useContentLocale('homepage', contentId);

    useEffect(() => {
        client
            .get('/admin/plugins/all')
            .then(({ data }) => setBuilderActive(data.some((plugin) => 'page_builder' === plugin.name && plugin.enabled)))
            .catch(() => setBuilderActive(false));
    }, []);

    useEffect(() => {
        if (null === builderActive) {
            return;
        }

        client
            .get('/admin/homepage')
            .then(({ data }) => {
                setContentValue(builderActive && data.builderData ? data.builderData : data.content);
                setContentId(data.id);
            })
            .catch(() => setError(t('homepage.loadError')))
            .finally(() => setLoading(false));
    }, [builderActive, t]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);
        setSaved(false);

        // The builder's JSON value is never sent as `content` directly -
        // it's rendered to plain HTML at save time, so the public site
        // never needs to know which editor produced it.
        let content = contentValue;
        let builderData = null;
        if (builderActive) {
            const { default: renderToHtml } = await import('page_builder/renderToHtml');
            content = renderToHtml(contentValue);
            builderData = contentValue;
        }

        try {
            await client.patch(
                '/admin/homepage',
                { content, builderData },
                { headers: { 'Content-Type': 'application/merge-patch+json' } },
            );
            await saveAllTranslations(contentId, { [builderActive ? 'builderData' : 'content']: contentValue }, builderActive ? 'builderData' : null);
            setSaved(true);
        } catch {
            setError(t('homepage.saveError'));
        }
    };

    if (loading || null === builderActive) {
        return <p>{t('homepage.loading')}</p>;
    }

    return (
        <div>
            <h1>{t('homepage.title')}</h1>

            {activeLangs.length > 1 && (
                <Nav variant="pills" className="mb-3">
                    {activeLangs.map((lang) => (
                        <Nav.Item key={lang.code}>
                            <Nav.Link active={lang.code === editingLocale} onClick={() => setEditingLocale(lang.code)}>
                                {lang.label}
                            </Nav.Link>
                        </Nav.Item>
                    ))}
                </Nav>
            )}

            {error && <div className="alert alert-danger">{error}</div>}
            {saved && <div className="alert alert-success">{t('homepage.saved')}</div>}

            <Form onSubmit={handleSubmit} style={{ maxWidth: '640px' }}>
                <Form.Group className="mb-3" controlId="homepageContent">
                    <Form.Label>{t('homepage.content')}</Form.Label>
                    {/* Editor only mounts once `loading`/`builderActive` are settled
                        above, so its initial value is already the real content. */}
                    {/* key={editingLocale}: forces a remount on language switch, see PageForm.jsx's comment. */}
                    <Suspense fallback={<p>{t('homepage.loadingEditor')}</p>}>
                        {builderActive ? (
                            <BuilderCanvas
                                key={editingLocale}
                                value={fieldValue(contentValue, 'builderData')}
                                onChange={setField('builderData', setContentValue)}
                            />
                        ) : (
                            <RichTextEditor
                                key={editingLocale}
                                value={fieldValue(contentValue, 'content')}
                                onChange={setField('content', setContentValue)}
                                placeholder={t('homepage.contentPlaceholder')}
                            />
                        )}
                    </Suspense>
                </Form.Group>

                <Button type="submit" variant="primary">
                    {t('common.save')}
                </Button>
            </Form>
        </div>
    );
}
