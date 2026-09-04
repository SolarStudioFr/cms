import React, { useEffect, useRef, useState } from 'react';
import { Button, ProgressBar } from 'react-bootstrap';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';

/**
 * Browser-driven bulk send (step 24): one fetch per recipient, calling
 * POST .../send-next in a loop and updating the progress bar between each
 * call - never a single blocking request that mails everyone server-side.
 * Naturally resumable: if the tab is closed mid-send, re-opening this page
 * and clicking the button again picks up exactly where it left off, since
 * send-next always skips subscribers that already have a CampaignSend row.
 */
export default function CampaignSend() {
    const { id } = useParams();
    const [campaign, setCampaign] = useState(null);
    const [progress, setProgress] = useState(null); // {sentCount, total, done}
    const [sending, setSending] = useState(false);
    const [error, setError] = useState(null);
    // Guards against a stray second loop if the button is clicked twice.
    const sendingRef = useRef(false);

    useEffect(() => {
        client.get(`/admin/newsletter/campaigns/${id}`).then(({ data }) => setCampaign(data));
    }, [id]);

    const sendNext = async () => {
        const { data } = await client.post(`/admin/newsletter/campaigns/${id}/send-next`);
        setProgress(data);

        return data;
    };

    const startSending = async () => {
        if (sendingRef.current) {
            return;
        }
        sendingRef.current = true;
        setSending(true);
        setError(null);

        try {
            let result = await sendNext();
            while (!result.done) {
                result = await sendNext();
            }
        } catch {
            setError("Échec de l'envoi - vous pouvez relancer, l'envoi reprendra là où il s'est arrêté.");
        } finally {
            sendingRef.current = false;
            setSending(false);
        }
    };

    if (!campaign) {
        return <p>Chargement...</p>;
    }

    const percent = progress && progress.total > 0 ? Math.round((100 * progress.sentCount) / progress.total) : 0;
    const done = progress ? progress.done : 'sent' === campaign.status;

    return (
        <div>
            <h1>Envoi : {campaign.subject}</h1>

            {error && <div className="alert alert-danger">{error}</div>}

            {progress && (
                <div className="mb-3" style={{ maxWidth: '480px' }}>
                    <ProgressBar now={percent} label={`${percent}%`} />
                    <p className="mt-2">
                        {progress.sentCount} / {progress.total} envoyés
                    </p>
                </div>
            )}

            {done ? (
                <p className="text-success">Envoi terminé.</p>
            ) : (
                <Button variant="primary" onClick={startSending} disabled={sending}>
                    {sending ? 'Envoi en cours...' : "sending" === campaign.status ? "Reprendre l'envoi" : "Démarrer l'envoi"}
                </Button>
            )}

            <div className="mt-3">
                <Button as={Link} to="/newsletter" variant="link">
                    Retour aux campagnes
                </Button>
            </div>
        </div>
    );
}
