import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Badge, Button, Card, Col, Form, Row } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from './api/client';
import useDomainTranslator from './useDomainTranslator';

// Both consumed from other plugins' Module Federation remotes - lazy since
// resolving a remote container is inherently async.
const RichTextEditor = lazy(() => import('adm_host/RichTextEditor'));
const BuilderCanvas = lazy(() => import('page_builder/BuilderCanvas'));
const MediaPicker = lazy(() => import('adm_host/MediaPicker'));
const ContentTranslationPanel = lazy(() => import('adm_host/ContentTranslationPanel'));

/** Client-side preview only - the real slug is always recomputed server-side by PortfolioItem::refreshSlug() on save. */
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

/** Admin create/edit form for a PortfolioItem (step 17), same editor-switch logic as Plugin\Page's PageForm, plus a cover image field, S.E.O. fields (step 37) and tags (step 38). */
export default function PortfolioItemForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { t } = useDomainTranslator('portfolio');
    const isEditing = Boolean(id);

    const [title, setTitle] = useState('');
    // Whichever editor is active, this holds its native value: builder JSON
    // when the builder is active, plain HTML otherwise.
    const [contentValue, setContentValue] = useState('');
    const [status, setStatus] = useState('draft');
    const [coverImageUrl, setCoverImageUrl] = useState('');
    const [coverImageAlt, setCoverImageAlt] = useState('');
    const [pickerOpen, setPickerOpen] = useState(false);

    // S.E.O. & social fields (step 37).
    const [seoTitle, setSeoTitle] = useState('');
    const [seoDescription, setSeoDescription] = useState('');
    const [ogImageUrl, setOgImageUrl] = useState('');
    const [ogType, setOgType] = useState('website');
    const [canonicalUrl, setCanonicalUrl] = useState('');
    const [ogPickerOpen, setOgPickerOpen] = useState(false);

    // Tags (step 38) - existing tags fetched once for the multi-select,
    // selection tracked as a Set of ids, new tags created on the fly.
    const [allTags, setAllTags] = useState([]);
    const [selectedTagIds, setSelectedTagIds] = useState([]);
    const [newTagName, setNewTagName] = useState('');
    const [tagError, setTagError] = useState(null);

    const [loading, setLoading] = useState(isEditing);
    const [error, setError] = useState(null);
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
        client
            .get('/admin/portfolio/tags')
            .then(({ data }) => setAllTags(data))
            .catch(() => setAllTags([]));
    }, []);

    useEffect(() => {
        if (!isEditing || null === builderActive) {
            return;
        }

        client
            .get(`/admin/portfolio/${id}`)
            .then(({ data }) => {
                setTitle(data.title);
                setStatus(data.status);
                setCoverImageUrl(data.coverImageUrl || '');
                setCoverImageAlt(data.coverImageAlt || '');
                setContentValue(builderActive && data.builderData ? data.builderData : data.content);
                setSeoTitle(data.seoTitle || '');
                setSeoDescription(data.seoDescription || '');
                setOgImageUrl(data.ogImageUrl || '');
                setOgType(data.ogType || 'website');
                setCanonicalUrl(data.canonicalUrl || '');
                setSelectedTagIds((data.tags || []).map((tag) => tag.id));
            })
            .catch(() => setError(t('portfolio.loadError')))
            .finally(() => setLoading(false));
    }, [id, isEditing, builderActive, t]);

    const toggleTag = (tagId) => {
        setSelectedTagIds((current) =>
            current.includes(tagId) ? current.filter((existing) => existing !== tagId) : [...current, tagId],
        );
    };

    const handleCreateTag = async () => {
        const name = newTagName.trim();
        if ('' === name) {
            return;
        }

        setTagError(null);
        try {
            const { data } = await client.post('/admin/portfolio/tags', { name });
            setAllTags((current) => [...current, data]);
            setSelectedTagIds((current) => [...current, data.id]);
            setNewTagName('');
        } catch {
            setTagError(t('portfolio.tagCreateError'));
        }
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);

        // The builder's JSON value is never sent as `content` directly -
        // it's rendered to plain HTML at save time, so the public site (and
        // anything else reading PortfolioItem.content) never needs to know
        // which editor produced it.
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
            coverImageUrl: coverImageUrl || null,
            coverImageAlt: coverImageAlt || null,
            seoTitle: seoTitle || null,
            seoDescription: seoDescription || null,
            ogImageUrl: ogImageUrl || null,
            ogType,
            canonicalUrl: canonicalUrl || null,
            tagIds: selectedTagIds,
        };

        try {
            if (isEditing) {
                await client.patch(
                    `/admin/portfolio/${id}`,
                    { ...payload, status },
                    { headers: { 'Content-Type': 'application/merge-patch+json' } },
                );
            } else {
                await client.post('/admin/portfolio', payload);
            }
            navigate('/portfolio');
        } catch {
            setError(t('common.saveError'));
        }
    };

    if (loading || null === builderActive) {
        return <p>{t('common.loading')}</p>;
    }

    return (
        <div>
            <h1>{isEditing ? t('portfolio.editTitle') : t('portfolio.newTitle')}</h1>

            {error && <div className="alert alert-danger">{error}</div>}

            <Form onSubmit={handleSubmit}>
                <Row>
                    <Col lg={8}>
                        <Card className="mb-3">
                            <Card.Header>{t('portfolio.identity')}</Card.Header>
                            <Card.Body>
                                <Form.Group className="mb-3" controlId="portfolioItemTitle">
                                    <Form.Label>{t('portfolio.titleColumn')}</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={title}
                                        onChange={(e) => setTitle(e.target.value)}
                                        required
                                    />
                                </Form.Group>
                                <Form.Group controlId="portfolioItemSlug">
                                    <Form.Label>{t('portfolio.slug')}</Form.Label>
                                    <Form.Control type="text" value={slugPreview(title)} disabled readOnly />
                                    <Form.Text className="text-muted">{t('portfolio.slugHint')}</Form.Text>
                                </Form.Group>
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>{t('portfolio.content')}</Card.Header>
                            <Card.Body>
                                {/* Editor only mounts once `loading`/`builderActive` are settled
                                    above, so its initial value is already the real content. */}
                                <Suspense fallback={<p>{t('portfolio.loadingEditor')}</p>}>
                                    {builderActive ? (
                                        <BuilderCanvas value={contentValue} onChange={setContentValue} />
                                    ) : (
                                        <RichTextEditor value={contentValue} onChange={setContentValue} placeholder={t('portfolio.contentPlaceholder')} />
                                    )}
                                </Suspense>
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>{t('portfolio.seo')}</Card.Header>
                            <Card.Body>
                                <Form.Group className="mb-3" controlId="portfolioItemSeoTitle">
                                    <Form.Label>{t('portfolio.seoTitle')}</Form.Label>
                                    <Form.Control type="text" value={seoTitle} onChange={(e) => setSeoTitle(e.target.value)} />
                                </Form.Group>
                                <Form.Group className="mb-3" controlId="portfolioItemSeoDescription">
                                    <Form.Label>{t('portfolio.metaDescription')}</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={seoDescription}
                                        onChange={(e) => setSeoDescription(e.target.value)}
                                    />
                                </Form.Group>
                                <Form.Group className="mb-3" controlId="portfolioItemOgImage">
                                    <Form.Label>{t('portfolio.ogImage')}</Form.Label>
                                    <div>
                                        {ogImageUrl ? (
                                            <img
                                                src={ogImageUrl}
                                                alt=""
                                                style={{ maxWidth: '240px', maxHeight: '160px', display: 'block', marginBottom: '8px' }}
                                            />
                                        ) : (
                                            <p className="text-muted small">{t('portfolio.noImageSelected')}</p>
                                        )}
                                        <Button size="sm" variant="outline-secondary" onClick={() => setOgPickerOpen(true)}>
                                            {ogImageUrl ? t('portfolio.changeImage') : t('portfolio.chooseImage')}
                                        </Button>
                                        {ogPickerOpen && (
                                            <Suspense fallback={null}>
                                                <MediaPicker
                                                    show={ogPickerOpen}
                                                    onHide={() => setOgPickerOpen(false)}
                                                    onSelect={(file) => setOgImageUrl(file.url)}
                                                    types={['img']}
                                                    title={t('portfolio.chooseOgImageTitle')}
                                                />
                                            </Suspense>
                                        )}
                                    </div>
                                </Form.Group>
                                <Form.Group className="mb-3" controlId="portfolioItemOgType">
                                    <Form.Label>{t('portfolio.ogType')}</Form.Label>
                                    <Form.Select value={ogType} onChange={(e) => setOgType(e.target.value)}>
                                        <option value="website">Website</option>
                                        <option value="article">Article</option>
                                        <option value="product">Product</option>
                                        <option value="profile">Profile</option>
                                    </Form.Select>
                                </Form.Group>
                                <Form.Group controlId="portfolioItemCanonicalUrl">
                                    <Form.Label>{t('portfolio.canonicalUrl')}</Form.Label>
                                    <Form.Control
                                        type="url"
                                        value={canonicalUrl}
                                        onChange={(e) => setCanonicalUrl(e.target.value)}
                                        placeholder="https://..."
                                    />
                                </Form.Group>
                            </Card.Body>
                        </Card>
                    </Col>

                    <Col lg={4}>
                        {isEditing && (
                            <Card className="mb-3">
                                <Card.Header>{t('portfolio.publication')}</Card.Header>
                                <Card.Body>
                                    <Form.Group controlId="portfolioItemStatus">
                                        <Form.Label>{t('portfolio.status')}</Form.Label>
                                        <Form.Select value={status} onChange={(e) => setStatus(e.target.value)}>
                                            <option value="draft">{t('portfolio.statusDraft')}</option>
                                            <option value="published">{t('portfolio.statusPublished')}</option>
                                            <option value="archived">{t('portfolio.statusArchived')}</option>
                                        </Form.Select>
                                    </Form.Group>
                                </Card.Body>
                            </Card>
                        )}

                        <Card className="mb-3">
                            <Card.Header>{t('portfolio.coverImage')}</Card.Header>
                            <Card.Body>
                                {coverImageUrl ? (
                                    <img
                                        src={coverImageUrl}
                                        alt={coverImageAlt}
                                        style={{ maxWidth: '100%', display: 'block', marginBottom: '8px' }}
                                    />
                                ) : (
                                    <p className="text-muted small">{t('portfolio.noImageSelected')}</p>
                                )}
                                <Button size="sm" variant="outline-secondary" className="mb-2" onClick={() => setPickerOpen(true)}>
                                    {coverImageUrl ? t('portfolio.changeImage') : t('portfolio.chooseImage')}
                                </Button>
                                {pickerOpen && (
                                    <Suspense fallback={null}>
                                        <MediaPicker
                                            show={pickerOpen}
                                            onHide={() => setPickerOpen(false)}
                                            onSelect={(file) => {
                                                setCoverImageUrl(file.url);
                                                setCoverImageAlt(coverImageAlt || file.name);
                                            }}
                                            types={['img']}
                                            title={t('portfolio.chooseCoverImageTitle')}
                                        />
                                    </Suspense>
                                )}
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>{t('portfolio.tags')}</Card.Header>
                            <Card.Body>
                                {tagError && <div className="alert alert-danger py-1 px-2 small">{tagError}</div>}
                                <div className="mb-2">
                                    {0 === allTags.length && <p className="text-muted small">{t('portfolio.noTags')}</p>}
                                    {allTags.map((tag) => {
                                        const active = selectedTagIds.includes(tag.id);
                                        return (
                                            <Badge
                                                key={tag.id}
                                                bg={active ? 'primary' : 'secondary'}
                                                role="button"
                                                className="me-1 mb-1"
                                                onClick={() => toggleTag(tag.id)}
                                            >
                                                {tag.name}
                                            </Badge>
                                        );
                                    })}
                                </div>
                                <Form.Group className="d-flex gap-2" controlId="portfolioItemNewTag">
                                    <Form.Control
                                        type="text"
                                        size="sm"
                                        placeholder={t('portfolio.newTag')}
                                        value={newTagName}
                                        onChange={(e) => setNewTagName(e.target.value)}
                                    />
                                    <Button size="sm" variant="outline-secondary" type="button" onClick={handleCreateTag}>
                                        {t('common.add')}
                                    </Button>
                                </Form.Group>
                            </Card.Body>
                        </Card>

                        <Suspense fallback={null}>
                            <ContentTranslationPanel
                                entityType="portfolio_item"
                                entityId={isEditing ? Number(id) : null}
                                fields={[
                                    { name: 'title', label: t('portfolio.titleColumn') },
                                    { name: 'content', label: t('portfolio.content'), type: 'html' },
                                    { name: 'seoTitle', label: t('portfolio.seoTitle') },
                                    { name: 'seoDescription', label: t('portfolio.metaDescription'), type: 'textarea' },
                                ]}
                            />
                        </Suspense>
                    </Col>
                </Row>

                <Button type="submit" variant="primary">
                    {t('common.save')}
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/portfolio')}>
                    {t('common.cancel')}
                </Button>
            </Form>
        </div>
    );
}
