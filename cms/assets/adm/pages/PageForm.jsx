import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Card, Col, Form, Nav, Row } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from '../api/client';
import RichTextEditor from '../components/RichTextEditor';
import MediaPicker from '../components/MediaPicker';
import useContentLocale from '../hooks/useContentLocale';
import { useTranslator } from '../i18n/TranslationContext';

// The page builder plugin (step 10-16) is the one dependency still consumed
// as a real Module Federation remote (see the `page_builder` static remote
// in the root webpack.config.js) - lazy since resolving a remote container
// is inherently async.
const BuilderCanvas = lazy(() => import('page_builder/BuilderCanvas'));

/** Client-side preview only - the real slug is always recomputed server-side by Page::refreshSlug() on save. */
function slugPreview(title) {
    // Strip combining diacritics (Unicode block 0x0300-0x036F) left behind by
    // NFD decomposition - written as a codepoint check rather than a regex
    // range literal to avoid encoding pitfalls with combining characters.
    let ascii = '';
    for (const char of title.normalize('NFD')) {
        const code = char.codePointAt(0);
        if (code < 0x0300 || code > 0x036f) {
            ascii += char;
        }
    }

    return ascii
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

export default function PageForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { t } = useTranslator();
    const isEditing = Boolean(id);

    const [title, setTitle] = useState('');
    // Whichever editor is active, this holds its native value: builder JSON
    // when the builder is active, plain HTML otherwise - see step 10/16.
    const [contentValue, setContentValue] = useState('');
    const [status, setStatus] = useState('draft');

    // S.E.O. & social fields (step 37).
    const [seoTitle, setSeoTitle] = useState('');
    const [seoDescription, setSeoDescription] = useState('');
    const [ogImageUrl, setOgImageUrl] = useState('');
    const [ogType, setOgType] = useState('website');
    const [canonicalUrl, setCanonicalUrl] = useState('');
    const [ogPickerOpen, setOgPickerOpen] = useState(false);

    // Featured image (step 37) - Page has no equivalent field before this step.
    const [featuredImageUrl, setFeaturedImageUrl] = useState('');
    const [featuredImageAlt, setFeaturedImageAlt] = useState('');
    const [featuredPickerOpen, setFeaturedPickerOpen] = useState(false);

    const [loading, setLoading] = useState(isEditing);
    const [error, setError] = useState(null);
    // null while still checking, so the form doesn't flash one editor then
    // swap to the other once the plugin list has loaded.
    const [builderActive, setBuilderActive] = useState(null);

    // Content-language switcher (step 58 follow-up): title/content/SEO
    // fields below are bound through fieldValue()/setField() so the same
    // form swaps between the base language and a translation.
    const { activeLangs, editingLocale, setEditingLocale, isDefaultLocale, fieldValue, setField, saveAllTranslations } =
        useContentLocale('page', isEditing ? Number(id) : null);

    useEffect(() => {
        client
            .get('/admin/plugins/all')
            .then(({ data }) => setBuilderActive(data.some((plugin) => 'page_builder' === plugin.name && plugin.enabled)))
            .catch(() => setBuilderActive(false));
    }, []);

    useEffect(() => {
        if (!isEditing || null === builderActive) {
            return;
        }

        client
            .get(`/admin/pages/${id}`)
            .then(({ data }) => {
                setTitle(data.title);
                setStatus(data.status);
                setContentValue(builderActive && data.builderData ? data.builderData : data.content);
                setSeoTitle(data.seoTitle || '');
                setSeoDescription(data.seoDescription || '');
                setOgImageUrl(data.ogImageUrl || '');
                setOgType(data.ogType || 'website');
                setCanonicalUrl(data.canonicalUrl || '');
                setFeaturedImageUrl(data.featuredImageUrl || '');
                setFeaturedImageAlt(data.featuredImageAlt || '');
            })
            .catch(() => setError(t('pageForm.loadError')))
            .finally(() => setLoading(false));
    }, [id, isEditing, builderActive, t]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);

        // The builder's JSON value is never sent as `content` directly -
        // it's rendered to plain HTML at save time, so the public site (and
        // anything else reading Page.content) never needs to know which
        // editor produced it.
        let content = contentValue;
        let builderData = null;
        if (builderActive) {
            const { default: renderToHtml } = await import('page_builder/renderToHtml');
            content = renderToHtml(contentValue);
            builderData = contentValue;
        }

        const payload = {
            title,
            content,
            builderData,
            seoTitle: seoTitle || null,
            seoDescription: seoDescription || null,
            ogImageUrl: ogImageUrl || null,
            ogType,
            canonicalUrl: canonicalUrl || null,
            featuredImageUrl: featuredImageUrl || null,
            featuredImageAlt: featuredImageAlt || null,
        };

        try {
            const savedId = isEditing
                ? Number(id)
                : (await client.post('/admin/pages', payload)).data.id;
            if (isEditing) {
                await client.patch(
                    `/admin/pages/${id}`,
                    { ...payload, status },
                    { headers: { 'Content-Type': 'application/merge-patch+json' } },
                );
            }
            await saveAllTranslations(
                savedId,
                { title, [builderActive ? 'builderData' : 'content']: contentValue, seoTitle, seoDescription },
                builderActive ? 'builderData' : null,
            );
            navigate('/pages');
        } catch {
            setError(t('common.saveError'));
        }
    };

    if (loading || null === builderActive) {
        return <p>{t('common.loading')}</p>;
    }

    return (
        <div>
            <h1>{isEditing ? t('pageForm.editTitle') : t('pageForm.newTitle')}</h1>

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

            <Form onSubmit={handleSubmit}>
                <Row>
                    <Col lg={8}>
                        <Card className="mb-3">
                            <Card.Header>{t('pageForm.identity')}</Card.Header>
                            <Card.Body>
                                <Form.Group className="mb-3" controlId="pageTitle">
                                    <Form.Label>{t('pageForm.titleField')}</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={fieldValue(title, 'title')}
                                        onChange={(e) => setField('title', setTitle)(e.target.value)}
                                        required={isDefaultLocale}
                                    />
                                </Form.Group>
                                {isDefaultLocale && (
                                    <Form.Group controlId="pageSlug">
                                        <Form.Label>{t('pageForm.slug')}</Form.Label>
                                        <Form.Control type="text" value={slugPreview(title)} disabled readOnly />
                                        <Form.Text className="text-muted">{t('pageForm.slugHint')}</Form.Text>
                                    </Form.Group>
                                )}
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>{t('pageForm.content')}</Card.Header>
                            <Card.Body>
                                {/* Editor only mounts once `loading`/`builderActive` are settled
                                    above, so its initial value is already the real content. */}
                                {/* key={editingLocale}: BuilderCanvas/RichTextEditor only seed
                                    their internal state once on mount (by design, so re-syncing
                                    on every keystroke doesn't fight the cursor) - without a key
                                    that changes with the active tab, switching languages would
                                    keep editing the same in-memory content regardless of which
                                    tab is shown. The key forces a full remount, reseeding from
                                    the new tab's own value. */}
                                <Suspense fallback={<p>{t('pageForm.loadingEditor')}</p>}>
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
                                            placeholder={t('pageForm.contentPlaceholder')}
                                        />
                                    )}
                                </Suspense>
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>{t('pageForm.seo')}</Card.Header>
                            <Card.Body>
                                <Form.Group className="mb-3" controlId="pageSeoTitle">
                                    <Form.Label>{t('pageForm.seoTitle')}</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={fieldValue(seoTitle, 'seoTitle')}
                                        onChange={(e) => setField('seoTitle', setSeoTitle)(e.target.value)}
                                    />
                                </Form.Group>
                                <Form.Group className="mb-3" controlId="pageSeoDescription">
                                    <Form.Label>{t('pageForm.metaDescription')}</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={fieldValue(seoDescription, 'seoDescription')}
                                        onChange={(e) => setField('seoDescription', setSeoDescription)(e.target.value)}
                                    />
                                </Form.Group>
                                {isDefaultLocale && (
                                    <>
                                        <Form.Group className="mb-3" controlId="pageOgImage">
                                            <Form.Label>{t('pageForm.ogImage')}</Form.Label>
                                            <div>
                                                {ogImageUrl ? (
                                                    <img
                                                        src={ogImageUrl}
                                                        alt=""
                                                        style={{ maxWidth: '240px', maxHeight: '160px', display: 'block', marginBottom: '8px' }}
                                                    />
                                                ) : (
                                                    <p className="text-muted small">{t('pageForm.noImageSelected')}</p>
                                                )}
                                                <Button size="sm" variant="outline-secondary" onClick={() => setOgPickerOpen(true)}>
                                                    {ogImageUrl ? t('pageForm.changeImage') : t('pageForm.chooseImage')}
                                                </Button>
                                                {ogPickerOpen && (
                                                    <MediaPicker
                                                        show={ogPickerOpen}
                                                        onHide={() => setOgPickerOpen(false)}
                                                        onSelect={(file) => setOgImageUrl(file.url)}
                                                        types={['img']}
                                                        title={t('pageForm.chooseOgImageTitle')}
                                                    />
                                                )}
                                            </div>
                                        </Form.Group>
                                        <Form.Group className="mb-3" controlId="pageOgType">
                                            <Form.Label>{t('pageForm.ogType')}</Form.Label>
                                            <Form.Select value={ogType} onChange={(e) => setOgType(e.target.value)}>
                                                <option value="website">Website</option>
                                                <option value="article">Article</option>
                                                <option value="product">Product</option>
                                                <option value="profile">Profile</option>
                                            </Form.Select>
                                        </Form.Group>
                                        <Form.Group controlId="pageCanonicalUrl">
                                            <Form.Label>{t('pageForm.canonicalUrl')}</Form.Label>
                                            <Form.Control
                                                type="url"
                                                value={canonicalUrl}
                                                onChange={(e) => setCanonicalUrl(e.target.value)}
                                                placeholder="https://..."
                                            />
                                        </Form.Group>
                                    </>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>

                    <Col lg={4}>
                        {isEditing && isDefaultLocale && (
                            <Card className="mb-3">
                                <Card.Header>{t('pageForm.publication')}</Card.Header>
                                <Card.Body>
                                    <Form.Group controlId="pageStatus">
                                        <Form.Label>{t('pageForm.status')}</Form.Label>
                                        <Form.Select value={status} onChange={(e) => setStatus(e.target.value)}>
                                            <option value="draft">{t('pageForm.statusDraft')}</option>
                                            <option value="published">{t('pageForm.statusPublished')}</option>
                                            <option value="archived">{t('pageForm.statusArchived')}</option>
                                        </Form.Select>
                                    </Form.Group>
                                </Card.Body>
                            </Card>
                        )}

                        {isDefaultLocale && (
                            <Card className="mb-3">
                                <Card.Header>{t('pageForm.featuredImage')}</Card.Header>
                                <Card.Body>
                                    {featuredImageUrl ? (
                                        <img
                                            src={featuredImageUrl}
                                            alt={featuredImageAlt}
                                            style={{ maxWidth: '100%', display: 'block', marginBottom: '8px' }}
                                        />
                                    ) : (
                                        <p className="text-muted small">{t('pageForm.noImageSelected')}</p>
                                    )}
                                    <Button size="sm" variant="outline-secondary" className="mb-2" onClick={() => setFeaturedPickerOpen(true)}>
                                        {featuredImageUrl ? t('pageForm.changeImage') : t('pageForm.chooseImage')}
                                    </Button>
                                    {featuredPickerOpen && (
                                        <MediaPicker
                                            show={featuredPickerOpen}
                                            onHide={() => setFeaturedPickerOpen(false)}
                                            onSelect={(file) => {
                                                setFeaturedImageUrl(file.url);
                                                setFeaturedImageAlt(featuredImageAlt || file.name);
                                            }}
                                            types={['img']}
                                            title={t('pageForm.chooseFeaturedImageTitle')}
                                        />
                                    )}
                                </Card.Body>
                            </Card>
                        )}
                    </Col>
                </Row>

                <Button type="submit" variant="primary">
                    {t('common.save')}
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/pages')}>
                    {t('common.cancel')}
                </Button>
            </Form>
        </div>
    );
}
