import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from '../api/client';
import { useTranslator } from '../i18n/TranslationContext';

const STATUS_VARIANT = {
    draft: 'secondary',
    published: 'success',
    archived: 'dark',
};

const STATUS_KEY = {
    draft: 'pageForm.statusDraft',
    published: 'pageForm.statusPublished',
    archived: 'pageForm.statusArchived',
};

export default function PageList() {
    const { t } = useTranslator();
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
        if (!window.confirm(t('pages.confirmDelete'))) {
            return;
        }
        await client.delete(`/admin/pages/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>{t('pages.title')}</h1>
                <Button as={Link} to="/pages/new" variant="primary">
                    {t('pages.create')}
                </Button>
            </div>

            {loading ? (
                <p>{t('common.loading')}</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>{t('pages.titleColumn')}</th>
                            <th>{t('pages.createdAt')}</th>
                            <th>{t('pages.status')}</th>
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
                                        {STATUS_KEY[page.status] ? t(STATUS_KEY[page.status]) : page.status}
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
                                        {t('common.edit')}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline-warning"
                                        className="me-2"
                                        onClick={() => archive(page.id)}
                                    >
                                        {t('pages.archive')}
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(page.id)}>
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
