import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';
import useDomainTranslator from './useDomainTranslator';

const STATUS_VARIANT = {
    draft: 'secondary',
    published: 'success',
    archived: 'dark',
};

const STATUS_KEY = {
    draft: 'news.statusDraft',
    published: 'news.statusPublished',
    archived: 'news.statusArchived',
};

/** Admin list of every NewsArticle (step 19), same shape as Plugin\Portfolio's PortfolioItemList. */
export default function NewsArticleList() {
    const { t } = useDomainTranslator('news');
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
        if (!window.confirm(t('news.confirmDelete'))) {
            return;
        }
        await client.delete(`/admin/news/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>{t('news.title')}</h1>
                <Button as={Link} to="/news/new" variant="primary">
                    {t('news.create')}
                </Button>
            </div>

            {loading ? (
                <p>{t('common.loading')}</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th></th>
                            <th>{t('news.titleColumn')}</th>
                            <th>{t('news.createdAt')}</th>
                            <th>{t('news.status')}</th>
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
                                        {STATUS_KEY[article.status] ? t(STATUS_KEY[article.status]) : article.status}
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
                                        {t('common.edit')}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline-warning"
                                        className="me-2"
                                        onClick={() => archive(article.id)}
                                    >
                                        {t('news.archive')}
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(article.id)}>
                                        {t('common.delete')}
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
