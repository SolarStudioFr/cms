import React, { useCallback, useEffect, useState } from 'react';
import { Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';
import useDomainTranslator from './useDomainTranslator';

/**
 * Admin list of newsletter subscribers (step 23): read + remove only -
 * subscribers are created either by the public signup form (step 25) or,
 * for now, directly in the database - no "add subscriber manually" form
 * was asked for.
 */
export default function SubscriberList() {
    const { t } = useDomainTranslator('newsletter');
    const [subscribers, setSubscribers] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/newsletter/subscribers')
            .then(({ data }) => setSubscribers(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const remove = async (id) => {
        if (!window.confirm(t('newsletter.confirmDeleteSubscriber'))) {
            return;
        }
        await client.delete(`/admin/newsletter/subscribers/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>{t('newsletter.subscribersTitle')}</h1>
                <Button as={Link} to="/newsletter" variant="outline-secondary">
                    {t('newsletter.backToCampaigns')}
                </Button>
            </div>

            {loading ? (
                <p>{t('newsletter.loading')}</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>{t('newsletter.name')}</th>
                            <th>{t('newsletter.email')}</th>
                            <th>{t('newsletter.locale')}</th>
                            <th>{t('newsletter.subscribedAt')}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {subscribers.map((subscriber) => (
                            <tr key={subscriber.id}>
                                <td>{subscriber.name || '—'}</td>
                                <td>{subscriber.email}</td>
                                <td>{subscriber.locale || '—'}</td>
                                <td>{new Date(subscriber.subscribedAt).toLocaleDateString()}</td>
                                <td>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(subscriber.id)}>
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
