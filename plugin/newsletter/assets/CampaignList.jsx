import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';

const STATUS_VARIANT = {
    draft: 'secondary',
    sending: 'warning',
    sent: 'success',
};

const STATUS_LABEL = {
    draft: 'Brouillon',
    sending: 'Envoi en cours',
    sent: 'Envoyée',
};

export default function CampaignList() {
    const [campaigns, setCampaigns] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/newsletter/campaigns')
            .then(({ data }) => setCampaigns(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const remove = async (id) => {
        if (!window.confirm('Supprimer cette campagne ?')) {
            return;
        }
        await client.delete(`/admin/newsletter/campaigns/${id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Newsletter</h1>
                <div>
                    <Button as={Link} to="/newsletter/subscribers" variant="outline-secondary" className="me-2">
                        Abonnés
                    </Button>
                    <Button as={Link} to="/newsletter/new" variant="primary">
                        Nouvelle campagne
                    </Button>
                </div>
            </div>

            {loading ? (
                <p>Chargement...</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>Sujet</th>
                            <th>Créée le</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {campaigns.map((campaign) => (
                            <tr key={campaign.id}>
                                <td>{campaign.subject}</td>
                                <td>{new Date(campaign.createdAt).toLocaleDateString()}</td>
                                <td>
                                    <Badge bg={STATUS_VARIANT[campaign.status] ?? 'secondary'}>
                                        {STATUS_LABEL[campaign.status] ?? campaign.status}
                                    </Badge>
                                </td>
                                <td>
                                    {'draft' === campaign.status && (
                                        <Button
                                            as={Link}
                                            to={`/newsletter/${campaign.id}/edit`}
                                            size="sm"
                                            variant="outline-secondary"
                                            className="me-2"
                                        >
                                            Éditer
                                        </Button>
                                    )}
                                    {'sent' !== campaign.status && (
                                        <Button
                                            as={Link}
                                            to={`/newsletter/${campaign.id}/send`}
                                            size="sm"
                                            variant="outline-success"
                                            className="me-2"
                                        >
                                            {'sending' === campaign.status ? "Reprendre l'envoi" : 'Envoyer'}
                                        </Button>
                                    )}
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(campaign.id)}>
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
