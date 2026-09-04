import React, { useEffect, useState } from 'react';
import { Container } from 'react-bootstrap';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';

export default function VerifyEmail() {
    const { token } = useParams();
    const [status, setStatus] = useState('checking'); // checking | success | error

    useEffect(() => {
        client
            .get(`/verify-email/${token}`)
            .then(() => setStatus('success'))
            .catch(() => setStatus('error'));
    }, [token]);

    return (
        <Container className="py-4">
            <h1>Vérification de l'email</h1>
            {'checking' === status && <p>Vérification en cours...</p>}
            {'success' === status && (
                <p className="text-success">
                    Votre adresse email a été vérifiée. Vous pouvez maintenant <Link to="/login">vous connecter</Link>.
                </p>
            )}
            {'error' === status && <p className="text-danger">Ce lien de vérification est invalide ou déjà utilisé.</p>}
        </Container>
    );
}
