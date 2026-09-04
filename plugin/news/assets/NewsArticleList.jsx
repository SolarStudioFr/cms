import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';

const STATUS_VARIANT = {
    draft: 'secondary',
    published: 'success',
    archived: 'dark',
};

/** Admin list of every NewsArticle (step 19), same shape as Plugin\Portfolio's PortfolioItemList. */
export default function NewsArticleList() {
    const [articles, setArticles] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/news')
            .then(({ data }) => setArticles(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const archive = async (id) => {
        await client.patch(
            `/admin/news/${id}`,
            { status: 'archived' },
            { headers: { 'Content-Type': 'application/merge-patch+json' } },
        );
        load();
    };

    const remove = async (id) => {
        if (!window.confirm('Supprimer cette actualité ?')) {
            return;
        }
        await client.delete(`/admin/news/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Actualités</h1>
                <Button as={Link} to="/news/new" variant="primary">
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
                        {articles.map((article) => (
                            <tr key={article.id}>
                                <td style={{ width: '64px' }}>
                                    {article.coverImageUrl && (
                                        <img
                                            src={article.coverImageUrl}
                                            alt={article.coverImageAlt || ''}
                                            style={{ width: '48px', height: '48px', objectFit: 'cover' }}
                                        />
                                    )}
                                </td>
                                <td>{article.title}</td>
                                <td>{new Date(article.createdAt).toLocaleDateString()}</td>
                                <td>
                                    <Badge bg={STATUS_VARIANT[article.status] ?? 'secondary'}>
                                        {article.status}
                                    </Badge>
                                </td>
                                <td>
                                    <Button
                                        as={Link}
                                        to={`/news/${article.id}/edit`}
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
                                        onClick={() => archive(article.id)}
                                    >
                                        Archiver
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(article.id)}>
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
