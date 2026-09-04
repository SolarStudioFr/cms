import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from './api/client';

// Both consumed from other plugins' Module Federation remotes - lazy since
// resolving a remote container is inherently async.
const RichTextEditor = lazy(() => import('adm_host/RichTextEditor'));
const BuilderCanvas = lazy(() => import('builder/BuilderCanvas'));
const MediaPicker = lazy(() => import('adm_host/MediaPicker'));

/** Admin create/edit form for a Realisation (step 17), same editor-switch logic as Plugin\Page's PageForm, plus a cover image field. */
export default function RealisationForm() {
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
    const [loading, setLoading] = useState(isEditing);
    const [error, setError] = useState(null);
    // null while still checking, so the form doesn't flash one editor then
    // swap to the other once the plugin list has loaded.
    const [builderActive, setBuilderActive] = useState(null);

    useEffect(() => {
        client
            .get('/admin/plugins/all')
            .then(({ data }) => setBuilderActive(data.some((plugin) => 'builder' === plugin.name && plugin.enabled)))
            .catch(() => setBuilderActive(false));
    }, []);

    useEffect(() => {
        if (!isEditing || null === builderActive) {
            return;
        }

        client
            .get(`/admin/realisations/${id}`)
            .then(({ data }) => {
                setTitle(data.title);
                setStatus(data.status);
                setCoverImageUrl(data.coverImageUrl || '');
                setCoverImageAlt(data.coverImageAlt || '');
                setContentValue(builderActive && data.builderData ? data.builderData : data.content);
            })
            .catch(() => setError('Impossible de charger la réalisation.'))
            .finally(() => setLoading(false));
    }, [id, isEditing, builderActive]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);

        // The builder's JSON value is never sent as `content` directly -
        // it's rendered to plain HTML at save time, so the public site (and
        // anything else reading Realisation.content) never needs to know
        // which editor produced it.
        let content = contentValue;
        let builderData = null;
        if (builderActive) {
            const { default: renderToHtml } = await import('builder/renderToHtml');
            content = renderToHtml(contentValue);
            builderData = contentValue;
        }

        try {
            if (isEditing) {
                await client.patch(
                    `/admin/realisations/${id}`,
                    { title, content, builderData, status, coverImageUrl: coverImageUrl || null, coverImageAlt: coverImageAlt || null },
                    { headers: { 'Content-Type': 'application/merge-patch+json' } },
                );
            } else {
                await client.post('/admin/realisations', {
                    title,
                    content,
                    builderData,
                    coverImageUrl: coverImageUrl || null,
                    coverImageAlt: coverImageAlt || null,
                });
            }
            navigate('/realisations');
        } catch {
            setError("Échec de l'enregistrement.");
        }
    };

    if (loading || null === builderActive) {
        return <p>Chargement...</p>;
    }

    return (
        <div>
            <h1>{isEditing ? 'Modifier la réalisation' : 'Nouvelle réalisation'}</h1>

            {error && <div className="alert alert-danger">{error}</div>}

            <Form onSubmit={handleSubmit} style={{ maxWidth: '640px' }}>
                <Form.Group className="mb-3" controlId="realisationTitle">
                    <Form.Label>Titre</Form.Label>
                    <Form.Control
                        type="text"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        required
                    />
                </Form.Group>

                <Form.Group className="mb-3" controlId="realisationCover">
                    <Form.Label>Image de couverture</Form.Label>
                    <div>
                        {coverImageUrl ? (
                            <img
                                src={coverImageUrl}
                                alt={coverImageAlt}
                                style={{ maxWidth: '240px', maxHeight: '160px', display: 'block', marginBottom: '8px' }}
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
                    </div>
                </Form.Group>

                <Form.Group className="mb-3" controlId="realisationContent">
                    <Form.Label>Contenu</Form.Label>
                    {/* Editor only mounts once `loading`/`builderActive` are settled
                        above, so its initial value is already the real content. */}
                    <Suspense fallback={<p>Chargement de l'éditeur...</p>}>
                        {builderActive ? (
                            <BuilderCanvas value={contentValue} onChange={setContentValue} />
                        ) : (
                            <RichTextEditor value={contentValue} onChange={setContentValue} placeholder="Contenu de la réalisation..." />
                        )}
                    </Suspense>
                </Form.Group>

                {isEditing && (
                    <Form.Group className="mb-3" controlId="realisationStatus">
                        <Form.Label>Statut</Form.Label>
                        <Form.Select value={status} onChange={(e) => setStatus(e.target.value)}>
                            <option value="draft">Brouillon</option>
                            <option value="published">Publiée</option>
                            <option value="archived">Archivée</option>
                        </Form.Select>
                    </Form.Group>
                )}

                <Button type="submit" variant="primary">
                    Enregistrer
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/realisations')}>
                    Annuler
                </Button>
            </Form>
        </div>
    );
}
