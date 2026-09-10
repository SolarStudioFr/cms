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
    draft: 'portfolio.statusDraft',
    published: 'portfolio.statusPublished',
    archived: 'portfolio.statusArchived',
};

/** Admin list of every PortfolioItem (step 17), same shape as Plugin\Page's PageList. */
export default function PortfolioItemList() {
    const { t } = useDomainTranslator('portfolio');
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/portfolio')
            .then(({ data }) => setItems(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const archive = async (id) => {
        await client.patch(
            `/admin/portfolio/${id}`,
            { status: 'archived' },
            { headers: { 'Content-Type': 'application/merge-patch+json' } },
        );
        load();
    };

    const remove = async (id) => {
        if (!window.confirm(t('portfolio.confirmDelete'))) {
            return;
        }
        await client.delete(`/admin/portfolio/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>{t('portfolio.title')}</h1>
                <Button as={Link} to="/portfolio/new" variant="primary">
                    {t('portfolio.create')}
                </Button>
            </div>

            {loading ? (
                <p>{t('common.loading')}</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th></th>
                            <th>{t('portfolio.titleColumn')}</th>
                            <th>{t('portfolio.createdAt')}</th>
                            <th>{t('portfolio.status')}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map((item) => (
                            <tr key={item.id}>
                                <td style={{ width: '64px' }}>
                                    {item.coverImageUrl && (
                                        <img
                                            src={item.coverImageUrl}
                                            alt={item.coverImageAlt || ''}
                                            style={{ width: '48px', height: '48px', objectFit: 'cover' }}
                                        />
                                    )}
                                </td>
                                <td>{item.title}</td>
                                <td>{new Date(item.createdAt).toLocaleDateString()}</td>
                                <td>
                                    <Badge bg={STATUS_VARIANT[item.status] ?? 'secondary'}>
                                        {STATUS_KEY[item.status] ? t(STATUS_KEY[item.status]) : item.status}
                                    </Badge>
                                </td>
                                <td>
                                    <Button
                                        as={Link}
                                        to={`/portfolio/${item.id}/edit`}
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
                                        onClick={() => archive(item.id)}
                                    >
                                        {t('portfolio.archive')}
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(item.id)}>
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
