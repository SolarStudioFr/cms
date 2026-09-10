import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Card, Col, Form, Nav, Row } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from './api/client';
import useDomainTranslator from './useDomainTranslator';
import useContentLocale from './useContentLocale';

// Both consumed from other plugins' Module Federation remotes - lazy since
// resolving a remote container is inherently async.
const RichTextEditor = lazy(() => import('adm_host/RichTextEditor'));
const BuilderCanvas = lazy(() => import('page_builder/BuilderCanvas'));
const MediaPicker = lazy(() => import('adm_host/MediaPicker'));

/** Client-side preview only - the real slug is always recomputed server-side by NewsArticle::refreshSlug() on save. */
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

/** Admin create/edit form for a NewsArticle (step 19), same shape as Plugin\Portfolio's PortfolioItemForm, plus a category (step 39) instead of tags. */
export default function NewsArticleForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { t } = useDomainTranslator('news');
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

    // Category (step 39) - existing categories fetched once for the select,
    // new ones created on the fly.
    const [allCategories, setAllCategories] = useState([]);
    const [categoryId, setCategoryId] = useState('');
    const [newCategoryName, setNewCategoryName] = useState('');
    const [categoryError, setCategoryError] = useState(null);

    const [loading, setLoading] = useState(isEditing);
    const [error, setError] = useState(null);
    // null while still checking, so the form doesn't flash one editor then
    // swap to the other once the plugin list has loaded.
    const [builderActive, setBuilderActive] = useState(null);

    // Content-language switcher (step 58 follow-up).
    const { activeLangs, editingLocale, setEditingLocale, isDefaultLocale, fieldValue, setField, saveAllTranslations } =
        useContentLocale('news_article', isEditing ? Number(id) : null);

    useEffect(() => {
        client
            .get('/admin/plugins/all')
            .then(({ data }) => setBuilderActive(data.some((plugin) => 'page_builder' === plugin.name && plugin.enabled)))
            .catch(() => setBuilderActive(false));
    }, []);

    useEffect(() => {
        client
            .get('/admin/news/categories')
            .then(({ data }) => setAllCategories(data))
            .catch(() => setAllCategories([]));
    }, []);

    useEffect(() => {
        if (!isEditing || null === builderActive) {
            return;
        }

        client
            .get(`/admin/news/${id}`)
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
                setCategoryId(data.category ? String(data.category.id) : '');
            })
            .catch(() => setError(t('news.loadError')))
            .finally(() => setLoading(false));
    }, [id, isEditing, builderActive, t]);

    const handleCreateCategory = async () => {
        const name = newCategoryName.trim();
        if ('' === name) {
            return;
        }

        setCategoryError(null);
        try {
            const { data } = await client.post('/admin/news/categories', { name });
            setAllCategories((current) => [...current, data]);
            setCategoryId(String(data.id));
            setNewCategoryName('');
        } catch {
            setCategoryError(t('news.categoryCreateError'));
        }
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);

        // The builder's JSON value is never sent as `content` directly -
        // it's rendered to plain HTML at save time, so the public site (and
        // anything else reading NewsArticle.content) never needs to know
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
            categoryId: '' === categoryId ? null : Number(categoryId),
        };

        try {
            const savedId = isEditing ? Number(id) : (await client.post('/admin/news', payload)).data.id;
            if (isEditing) {
                await client.patch(
                    `/admin/news/${id}`,
                    { ...payload, status },
                    { headers: { 'Content-Type': 'application/merge-patch+json' } },
                );
            }
            await saveAllTranslations(
                savedId,
                { title, [builderActive ? 'builderData' : 'content']: contentValue, seoTitle, seoDescription },
                builderActive ? 'builderData' : null,
            );
            navigate('/news');
        } catch {
            setError(t('common.saveError'));
        }
    };

    if (loading || null === builderActive) {
        return <p>{t('common.loading')}</p>;
    }

    return (
        <div>
            <h1>{isEditing ? t('news.editTitle') : t('news.newTitle')}</h1>

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
                            <Card.Header>{t('news.identity')}</Card.Header>
                            <Card.Body>
                                <Form.Group className="mb-3" controlId="newsArticleTitle">
                                    <Form.Label>{t('news.titleColumn')}</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={fieldValue(title, 'title')}
                                        onChange={(e) => setField('title', setTitle)(e.target.value)}
                                        required={isDefaultLocale}
                                    />
                                </Form.Group>
                                {isDefaultLocale && (
                                    <Form.Group controlId="newsArticleSlug">
                                        <Form.Label>{t('news.slug')}</Form.Label>
                                        <Form.Control type="text" value={slugPreview(title)} disabled readOnly />
                                        <Form.Text className="text-muted">{t('news.slugHint')}</Form.Text>
                                    </Form.Group>
                                )}
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>{t('news.content')}</Card.Header>
                            <Card.Body>
                                {/* Editor only mounts once `loading`/`builderActive` are settled
                                    above, so its initial value is already the real content. */}
                                {/* key={editingLocale}: forces a remount on language switch, see PageForm.jsx's comment. */}
                                <Suspense fallback={<p>{t('news.loadingEditor')}</p>}>
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
                                            placeholder={t('news.contentPlaceholder')}
                                        />
                                    )}
                                </Suspense>
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>{t('news.seo')}</Card.Header>
                            <Card.Body>
                                <Form.Group className="mb-3" controlId="newsArticleSeoTitle">
                                    <Form.Label>{t('news.seoTitle')}</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={fieldValue(seoTitle, 'seoTitle')}
                                        onChange={(e) => setField('seoTitle', setSeoTitle)(e.target.value)}
                                    />
                                </Form.Group>
                                <Form.Group className="mb-3" controlId="newsArticleSeoDescription">
                                    <Form.Label>{t('news.metaDescription')}</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={fieldValue(seoDescription, 'seoDescription')}
                                        onChange={(e) => setField('seoDescription', setSeoDescription)(e.target.value)}
                                    />
                                </Form.Group>
                                {isDefaultLocale && (
                                    <>
                                        <Form.Group className="mb-3" controlId="newsArticleOgImage">
                                            <Form.Label>{t('news.ogImage')}</Form.Label>
                                            <div>
                                                {ogImageUrl ? (
                                                    <img
                                                        src={ogImageUrl}
                                                        alt=""
                                                        style={{ maxWidth: '240px', maxHeight: '160px', display: 'block', marginBottom: '8px' }}
                                                    />
                                                ) : (
                                                    <p className="text-muted small">{t('news.noImageSelected')}</p>
                                                )}
                                                <Button size="sm" variant="outline-secondary" onClick={() => setOgPickerOpen(true)}>
                                                    {ogImageUrl ? t('news.changeImage') : t('news.chooseImage')}
                                                </Button>
                                                {ogPickerOpen && (
                                                    <Suspense fallback={null}>
                                                        <MediaPicker
                                                            show={ogPickerOpen}
                                                            onHide={() => setOgPickerOpen(false)}
                                                            onSelect={(file) => setOgImageUrl(file.url)}
                                                            types={['img']}
                                                            title={t('news.chooseOgImageTitle')}
                                                        />
                                                    </Suspense>
                                                )}
                                            </div>
                                        </Form.Group>
                                        <Form.Group className="mb-3" controlId="newsArticleOgType">
                                            <Form.Label>{t('news.ogType')}</Form.Label>
                                            <Form.Select value={ogType} onChange={(e) => setOgType(e.target.value)}>
                                                <option value="website">Website</option>
                                                <option value="article">Article</option>
                                                <option value="product">Product</option>
                                                <option value="profile">Profile</option>
                                            </Form.Select>
                                        </Form.Group>
                                        <Form.Group controlId="newsArticleCanonicalUrl">
                                            <Form.Label>{t('news.canonicalUrl')}</Form.Label>
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
                                <Card.Header>{t('news.publication')}</Card.Header>
                                <Card.Body>
                                    <Form.Group controlId="newsArticleStatus">
                                        <Form.Label>{t('news.status')}</Form.Label>
                                        <Form.Select value={status} onChange={(e) => setStatus(e.target.value)}>
                                            <option value="draft">{t('news.statusDraft')}</option>
                                            <option value="published">{t('news.statusPublished')}</option>
                                            <option value="archived">{t('news.statusArchived')}</option>
                                        </Form.Select>
                                    </Form.Group>
                                </Card.Body>
                            </Card>
                        )}

                        {isDefaultLocale && (
                            <>
                                <Card className="mb-3">
                                    <Card.Header>{t('news.coverImage')}</Card.Header>
                                    <Card.Body>
                                        {coverImageUrl ? (
                                            <img
                                                src={coverImageUrl}
                                                alt={coverImageAlt}
                                                style={{ maxWidth: '100%', display: 'block', marginBottom: '8px' }}
                                            />
                                        ) : (
                                            <p className="text-muted small">{t('news.noImageSelected')}</p>
                                        )}
                                        <Button size="sm" variant="outline-secondary" className="mb-2" onClick={() => setPickerOpen(true)}>
                                            {coverImageUrl ? t('news.changeImage') : t('news.chooseImage')}
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
                                                    title={t('news.chooseCoverImageTitle')}
                                                />
                                            </Suspense>
                                        )}
                                    </Card.Body>
                                </Card>

                                <Card className="mb-3">
                                    <Card.Header>{t('news.category')}</Card.Header>
                                    <Card.Body>
                                        {categoryError && <div className="alert alert-danger py-1 px-2 small">{categoryError}</div>}
                                        <Form.Group className="mb-2" controlId="newsArticleCategory">
                                            <Form.Select value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
                                                <option value="">{t('news.noCategory')}</option>
                                                {allCategories.map((category) => (
                                                    <option key={category.id} value={category.id}>
                                                        {category.name}
                                                    </option>
                                                ))}
                                            </Form.Select>
                                        </Form.Group>
                                        <Form.Group className="d-flex gap-2" controlId="newsArticleNewCategory">
                                            <Form.Control
                                                type="text"
                                                size="sm"
                                                placeholder={t('news.newCategory')}
                                                value={newCategoryName}
                                                onChange={(e) => setNewCategoryName(e.target.value)}
                                            />
                                            <Button size="sm" variant="outline-secondary" type="button" onClick={handleCreateCategory}>
                                                {t('common.add')}
                                            </Button>
                                        </Form.Group>
                                    </Card.Body>
                                </Card>
                            </>
                        )}
                    </Col>
                </Row>

                <Button type="submit" variant="primary">
                    {t('common.save')}
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/news')}>
                    {t('common.cancel')}
                </Button>
            </Form>
        </div>
    );
}
