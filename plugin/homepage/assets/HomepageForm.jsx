import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import client from './api/client';

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
    // Whichever editor is active, this holds its native value: builder JSON
    // when the builder is active, plain HTML otherwise.
    const [contentValue, setContentValue] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    // Transient success feedback: unlike the other content plugins' forms,
    // saving here never navigates away (no list to return to), so the form
    // needs its own visible confirmation.
    const [saved, setSaved] = useState(false);
    // null while still checking, so the form doesn't flash one editor then
    // swap to the other once the plugin list has loaded.
    const [builderActive, setBuilderActive] = useState(null);

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
            .then(({ data }) => setContentValue(builderActive && data.builderData ? data.builderData : data.content))
            .catch(() => setError("Impossible de charger le contenu de la page d'accueil."))
            .finally(() => setLoading(false));
    }, [builderActive]);

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
            setSaved(true);
        } catch {
            setError("Échec de l'enregistrement.");
        }
    };

    if (loading || null === builderActive) {
        return <p>Chargement...</p>;
    }

    return (
        <div>
            <h1>Page d'accueil</h1>

            {error && <div className="alert alert-danger">{error}</div>}
            {saved && <div className="alert alert-success">Page d'accueil enregistrée.</div>}

            <Form onSubmit={handleSubmit} style={{ maxWidth: '640px' }}>
                <Form.Group className="mb-3" controlId="homepageContent">
                    <Form.Label>Contenu</Form.Label>
                    {/* Editor only mounts once `loading`/`builderActive` are settled
                        above, so its initial value is already the real content. */}
                    <Suspense fallback={<p>Chargement de l'éditeur...</p>}>
                        {builderActive ? (
                            <BuilderCanvas value={contentValue} onChange={setContentValue} />
                        ) : (
                            <RichTextEditor value={contentValue} onChange={setContentValue} placeholder="Contenu de la page d'accueil..." />
                        )}
                    </Suspense>
                </Form.Group>

                <Button type="submit" variant="primary">
                    Enregistrer
                </Button>
            </Form>
        </div>
    );
}
