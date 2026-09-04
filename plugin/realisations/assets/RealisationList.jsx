import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';

const STATUS_VARIANT = {
    draft: 'secondary',
    published: 'success',
    archived: 'dark',
};

/** Admin list of every Realisation (step 17), same shape as Plugin\Page's PageList. */
export default function RealisationList() {
    const [realisations, setRealisations] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/realisations')
            .then(({ data }) => setRealisations(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const archive = async (id) => {
        await client.patch(
            `/admin/realisations/${id}`,
            { status: 'archived' },
            { headers: { 'Content-Type': 'application/merge-patch+json' } },
        );
        load();
    };

    const remove = async (id) => {
        if (!window.confirm('Supprimer cette réalisation ?')) {
            return;
        }
        await client.delete(`/admin/realisations/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Réalisations</h1>
                <Button as={Link} to="/realisations/new" variant="primary">
                    Créer une réalisation
                </Button>
            </div>

            {loading ? (
                <p>Chargement...</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Titre</th>
                            <th>Créée le</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {realisations.map((realisation) => (
                            <tr key={realisation.id}>
                                <td style={{ width: '64px' }}>
                                    {realisation.coverImageUrl && (
                                        <img
                                            src={realisation.coverImageUrl}
                                            alt={realisation.coverImageAlt || ''}
                                            style={{ width: '48px', height: '48px', objectFit: 'cover' }}
                                        />
                                    )}
                                </td>
                                <td>{realisation.title}</td>
                                <td>{new Date(realisation.createdAt).toLocaleDateString()}</td>
                                <td>
                                    <Badge bg={STATUS_VARIANT[realisation.status] ?? 'secondary'}>
                                        {realisation.status}
                                    </Badge>
                                </td>
                                <td>
                                    <Button
                                        as={Link}
                                        to={`/realisations/${realisation.id}/edit`}
                                        size="sm"
                                        variant="outline-secondary"
                                        className="me-2"
                                    >
                                        Éditer
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline-warning"
                                        className="me-2"
                                        onClick={() => archive(realisation.id)}
                                    >
                                        Archiver
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(realisation.id)}>
                                        Supprimer
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            )}
        </div>
    );
}
