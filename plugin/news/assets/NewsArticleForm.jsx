import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Card, Col, Form, Row } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from './api/client';

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
            .catch(() => setError("Impossible de charger l'actualité."))
            .finally(() => setLoading(false));
    }, [id, isEditing, builderActive]);

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
            setCategoryError('Échec de la création de la catégorie.');
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
            if (isEditing) {
                await client.patch(
                    `/admin/news/${id}`,
                    { ...payload, status },
                    { headers: { 'Content-Type': 'application/merge-patch+json' } },
                );
            } else {
                await client.post('/admin/news', payload);
            }
            navigate('/news');
        } catch {
            setError("Échec de l'enregistrement.");
        }
    };

    if (loading || null === builderActive) {
        return <p>Chargement...</p>;
    }

    return (
        <div>
            <h1>{isEditing ? "Modifier l'actualité" : 'Nouvelle actualité'}</h1>

            {error && <div className="alert alert-danger">{error}</div>}

            <Form onSubmit={handleSubmit}>
                <Row>
                    <Col lg={8}>
                        <Card className="mb-3">
                            <Card.Header>Identité</Card.Header>
                            <Card.Body>
                                <Form.Group className="mb-3" controlId="newsArticleTitle">
                                    <Form.Label>Titre</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={title}
                                        onChange={(e) => setTitle(e.target.value)}
                                        required
                                    />
                                </Form.Group>
                                <Form.Group controlId="newsArticleSlug">
                                    <Form.Label>Slug</Form.Label>
                                    <Form.Control type="text" value={slugPreview(title)} disabled readOnly />
                                    <Form.Text className="text-muted">Généré automatiquement à partir du titre.</Form.Text>
                                </Form.Group>
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>Contenu</Card.Header>
                            <Card.Body>
                                {/* Editor only mounts once `loading`/`builderActive` are settled
                                    above, so its initial value is already the real content. */}
                                <Suspense fallback={<p>Chargement de l'éditeur...</p>}>
                                    {builderActive ? (
                                        <BuilderCanvas value={contentValue} onChange={setContentValue} />
                                    ) : (
                                        <RichTextEditor value={contentValue} onChange={setContentValue} placeholder="Contenu de l'actualité..." />
                                    )}
                                </Suspense>
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>S.E.O. &amp; réseaux sociaux</Card.Header>
                            <Card.Body>
                                <Form.Group className="mb-3" controlId="newsArticleSeoTitle">
                                    <Form.Label>Titre S.E.O.</Form.Label>
                                    <Form.Control type="text" value={seoTitle} onChange={(e) => setSeoTitle(e.target.value)} />
                                </Form.Group>
                                <Form.Group className="mb-3" controlId="newsArticleSeoDescription">
                                    <Form.Label>Meta description</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={seoDescription}
                                        onChange={(e) => setSeoDescription(e.target.value)}
                                    />
                                </Form.Group>
                                <Form.Group className="mb-3" controlId="newsArticleOgImage">
                                    <Form.Label>Image OG</Form.Label>
                                    <div>
                                        {ogImageUrl ? (
                                            <img
                                                src={ogImageUrl}
                                                alt=""
                                                style={{ maxWidth: '240px', maxHeight: '160px', display: 'block', marginBottom: '8px' }}
                                            />
                                        ) : (
                                            <p className="text-muted small">Aucune image sélectionnée.</p>
                                        )}
                                        <Button size="sm" variant="outline-secondary" onClick={() => setOgPickerOpen(true)}>
                                            {ogImageUrl ? "Changer l'image" : 'Choisir une image'}
                                        </Button>
                                        {ogPickerOpen && (
                                            <Suspense fallback={null}>
                                                <MediaPicker
                                                    show={ogPickerOpen}
                                                    onHide={() => setOgPickerOpen(false)}
                                                    onSelect={(file) => setOgImageUrl(file.url)}
                                                    types={['img']}
                                                    title="Choisir une image OG"
                                                />
                                            </Suspense>
                                        )}
                                    </div>
                                </Form.Group>
                                <Form.Group className="mb-3" controlId="newsArticleOgType">
                                    <Form.Label>Type OG</Form.Label>
                                    <Form.Select value={ogType} onChange={(e) => setOgType(e.target.value)}>
                                        <option value="website">Website</option>
                                        <option value="article">Article</option>
                                        <option value="product">Product</option>
                                        <option value="profile">Profile</option>
                                    </Form.Select>
                                </Form.Group>
                                <Form.Group controlId="newsArticleCanonicalUrl">
                                    <Form.Label>URL canonique</Form.Label>
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
                                <Card.Header>Publication</Card.Header>
                                <Card.Body>
                                    <Form.Group controlId="newsArticleStatus">
                                        <Form.Label>Statut</Form.Label>
                                        <Form.Select value={status} onChange={(e) => setStatus(e.target.value)}>
                                            <option value="draft">Brouillon</option>
                                            <option value="published">Publiée</option>
                                            <option value="archived">Archivée</option>
                                        </Form.Select>
                                    </Form.Group>
                                </Card.Body>
                            </Card>
                        )}

                        <Card className="mb-3">
                            <Card.Header>Image à la une</Card.Header>
                            <Card.Body>
                                {coverImageUrl ? (
                                    <img
                                        src={coverImageUrl}
                                        alt={coverImageAlt}
                                        style={{ maxWidth: '100%', display: 'block', marginBottom: '8px' }}
                                    />
                                ) : (
                                    <p className="text-muted small">Aucune image sélectionnée.</p>
                                )}
                                <Button size="sm" variant="outline-secondary" className="mb-2" onClick={() => setPickerOpen(true)}>
                                    {coverImageUrl ? "Changer l'image" : 'Choisir une image'}
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
                                            title="Choisir une image de couverture"
                                        />
                                    </Suspense>
                                )}
                            </Card.Body>
                        </Card>

                        <Card className="mb-3">
                            <Card.Header>Catégorie</Card.Header>
                            <Card.Body>
                                {categoryError && <div className="alert alert-danger py-1 px-2 small">{categoryError}</div>}
                                <Form.Group className="mb-2" controlId="newsArticleCategory">
                                    <Form.Select value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
                                        <option value="">Aucune catégorie</option>
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
                                        placeholder="Nouvelle catégorie"
                                        value={newCategoryName}
                                        onChange={(e) => setNewCategoryName(e.target.value)}
                                    />
                                    <Button size="sm" variant="outline-secondary" type="button" onClick={handleCreateCategory}>
                                        Ajouter
                                    </Button>
                                </Form.Group>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>

                <Button type="submit" variant="primary">
                    Enregistrer
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/news')}>
                    Annuler
                </Button>
            </Form>
        </div>
    );
}
