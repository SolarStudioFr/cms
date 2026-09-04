import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from './api/client';

// Shared with the rest of the admin via Module Federation (step 09) - no
// page-builder integration for campaign content, see Campaign entity's docblock.
const RichTextEditor = lazy(() => import('adm_host/RichTextEditor'));

export default function CampaignForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const isEditing = Boolean(id);

    const [subject, setSubject] = useState('');
    const [content, setContent] = useState('');
    const [loading, setLoading] = useState(isEditing);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!isEditing) {
            return;
        }
        client
            .get(`/admin/newsletter/campaigns/${id}`)
            .then(({ data }) => {
                setSubject(data.subject);
                setContent(data.content);
            })
            .catch(() => setError('Impossible de charger la campagne.'))
            .finally(() => setLoading(false));
    }, [id, isEditing]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);

        try {
            if (isEditing) {
                await client.patch(
                    `/admin/newsletter/campaigns/${id}`,
                    { subject, content },
                    { headers: { 'Content-Type': 'application/merge-patch+json' } },
                );
            } else {
                await client.post('/admin/newsletter/campaigns', { subject, content });
            }
            navigate('/newsletter');
        } catch {
            setError("Échec de l'enregistrement.");
        }
    };

    if (loading) {
        return <p>Chargement...</p>;
    }

    return (
        <div>
            <h1>{isEditing ? 'Modifier la campagne' : 'Nouvelle campagne'}</h1>

            {error && <div className="alert alert-danger">{error}</div>}

            <Form onSubmit={handleSubmit} style={{ maxWidth: '640px' }}>
                <Form.Group className="mb-3" controlId="campaignSubject">
                    <Form.Label>Sujet</Form.Label>
                    <Form.Control
                        type="text"
                        value={subject}
                        onChange={(e) => setSubject(e.target.value)}
                        required
                    />
                </Form.Group>

                <Form.Group className="mb-3" controlId="campaignContent">
                    <Form.Label>Contenu</Form.Label>
                    <Suspense fallback={<p>Chargement de l'éditeur...</p>}>
                        <RichTextEditor value={content} onChange={setContent} placeholder="Contenu de la campagne..." />
                    </Suspense>
                </Form.Group>

                <Button type="submit" variant="primary">
                    Enregistrer
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/newsletter')}>
                    Annuler
                </Button>
            </Form>
        </div>
    );
}
