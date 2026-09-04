import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';

const STATUS_VARIANT = {
    draft: 'secondary',
    published: 'success',
    archived: 'dark',
};

/** Admin list of every Actualite (step 19), same shape as Plugin\Realisations's RealisationList. */
export default function ActualiteList() {
    const [actualites, setActualites] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/actualites')
            .then(({ data }) => setActualites(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const archive = async (id) => {
        await client.patch(
            `/admin/actualites/${id}`,
            { status: 'archived' },
            { headers: { 'Content-Type': 'application/merge-patch+json' } },
        );
        load();
    };

    const remove = async (id) => {
        if (!window.confirm('Supprimer cette actualité ?')) {
            return;
        }
        await client.delete(`/admin/actualites/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Actualités</h1>
                <Button as={Link} to="/actualites/new" variant="primary">
                    Créer une actualité
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
                        {actualites.map((actualite) => (
                            <tr key={actualite.id}>
                                <td style={{ width: '64px' }}>
                                    {actualite.coverImageUrl && (
                                        <img
                                            src={actualite.coverImageUrl}
                                            alt={actualite.coverImageAlt || ''}
                                            style={{ width: '48px', height: '48px', objectFit: 'cover' }}
                                        />
                                    )}
                                </td>
                                <td>{actualite.title}</td>
                                <td>{new Date(actualite.createdAt).toLocaleDateString()}</td>
                                <td>
                                    <Badge bg={STATUS_VARIANT[actualite.status] ?? 'secondary'}>
                                        {actualite.status}
                                    </Badge>
                                </td>
                                <td>
                                    <Button
                                        as={Link}
                                        to={`/actualites/${actualite.id}/edit`}
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
                                        onClick={() => archive(actualite.id)}
                                    >
                                        Archiver
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(actualite.id)}>
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
