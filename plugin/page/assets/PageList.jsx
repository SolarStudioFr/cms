import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';

const STATUS_VARIANT = {
    draft: 'secondary',
    published: 'success',
    archived: 'dark',
};

export default function PageList() {
    const [pages, setPages] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/pages')
            .then(({ data }) => setPages(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const archive = async (id) => {
        await client.patch(
            `/admin/pages/${id}`,
            { status: 'archived' },
            { headers: { 'Content-Type': 'application/merge-patch+json' } },
        );
        load();
    };

    const remove = async (id) => {
        if (!window.confirm('Supprimer cette page ?')) {
            return;
        }
        await client.delete(`/admin/pages/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Pages</h1>
                <Button as={Link} to="/pages/new" variant="primary">
                    Créer une page
                </Button>
            </div>

            {loading ? (
                <p>Chargement...</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Créée le</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {pages.map((page) => (
                            <tr key={page.id}>
                                <td>{page.title}</td>
                                <td>{new Date(page.createdAt).toLocaleDateString()}</td>
                                <td>
                                    <Badge bg={STATUS_VARIANT[page.status] ?? 'secondary'}>
                                        {page.status}
                                    </Badge>
                                </td>
                                <td>
                                    <Button
                                        as={Link}
                                        to={`/pages/${page.id}/edit`}
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
                                        onClick={() => archive(page.id)}
                                    >
                                        Archiver
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(page.id)}>
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
