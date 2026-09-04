import React, { useCallback, useEffect, useState } from 'react';
import { Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';

/**
 * Admin list of newsletter subscribers (step 23): read + remove only -
 * subscribers are created either by the public signup form (step 25) or,
 * for now, directly in the database - no "add subscriber manually" form
 * was asked for.
 */
export default function SubscriberList() {
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
        if (!window.confirm('Supprimer cet abonné ?')) {
            return;
        }
        await client.delete(`/admin/newsletter/subscribers/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Abonnés newsletter</h1>
                <Button as={Link} to="/newsletter" variant="outline-secondary">
                    Retour aux campagnes
                </Button>
            </div>

            {loading ? (
                <p>Chargement...</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Inscrit le</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {subscribers.map((subscriber) => (
                            <tr key={subscriber.id}>
                                <td>{subscriber.email}</td>
                                <td>{new Date(subscriber.subscribedAt).toLocaleDateString()}</td>
                                <td>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(subscriber.id)}>
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
