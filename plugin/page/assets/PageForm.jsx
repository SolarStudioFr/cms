import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from './api/client';

// Consumed from the admin host's Module Federation remote (step 09) rather
// than duplicated here - see webpack.config.js's `remotes` entry. Lazy since
// resolving a remote container is inherently async.
const RichTextEditor = lazy(() => import('adm_host/RichTextEditor'));

export default function PageForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const isEditing = Boolean(id);

    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [status, setStatus] = useState('draft');
    const [loading, setLoading] = useState(isEditing);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!isEditing) {
            return;
        }

        client
            .get(`/admin/pages/${id}`)
            .then(({ data }) => {
                setTitle(data.title);
                setContent(data.content);
                setStatus(data.status);
            })
            .catch(() => setError("Impossible de charger la page."))
            .finally(() => setLoading(false));
    }, [id, isEditing]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);

        try {
            if (isEditing) {
                await client.patch(
                    `/admin/pages/${id}`,
                    { title, content, status },
                    { headers: { 'Content-Type': 'application/merge-patch+json' } },
                );
            } else {
                await client.post('/admin/pages', { title, content });
            }
            navigate('/pages');
        } catch {
            setError("Échec de l'enregistrement.");
        }
    };

    if (loading) {
        return <p>Chargement...</p>;
    }

    return (
        <div>
            <h1>{isEditing ? 'Modifier la page' : 'Nouvelle page'}</h1>

            {error && <div className="alert alert-danger">{error}</div>}

            <Form onSubmit={handleSubmit} style={{ maxWidth: '640px' }}>
                <Form.Group className="mb-3" controlId="pageTitle">
                    <Form.Label>Titre</Form.Label>
                    <Form.Control
                        type="text"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        required
                    />
                </Form.Group>

                <Form.Group className="mb-3" controlId="pageContent">
                    <Form.Label>Contenu</Form.Label>
                    {/* RichTextEditor only mounts once `loading` is false above,
                        so its initial `value` is already the real content. */}
                    <Suspense fallback={<p>Chargement de l'éditeur...</p>}>
                        <RichTextEditor value={content} onChange={setContent} placeholder="Contenu de la page..." />
                    </Suspense>
                </Form.Group>

                {isEditing && (
                    <Form.Group className="mb-3" controlId="pageStatus">
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
                <Button type="button" variant="link" onClick={() => navigate('/pages')}>
                    Annuler
                </Button>
            </Form>
        </div>
    );
}
