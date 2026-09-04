import React, { useState } from 'react';
import { Button, Container, Form } from 'react-bootstrap';
import { Navigate } from 'react-router-dom';
import { useAuth } from './auth/AuthContext';
import client from './api/client';

/**
 * Member profile/account editing (step 27). Password change only - email
 * is the login identifier, changing it would need its own re-verification
 * flow (not asked for), so it's shown read-only here.
 */
export default function Profile() {
    const { user, loading, refresh } = useAuth();
    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [status, setStatus] = useState('idle'); // idle | saving | done | error
    const [error, setError] = useState(null);

    if (loading) {
        return null;
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    const handleSubmit = async (event) => {
        event.preventDefault();
        setStatus('saving');
        setError(null);

        try {
            await client.patch('/profile', { currentPassword, newPassword });
            await refresh();
            setCurrentPassword('');
            setNewPassword('');
            setStatus('done');
        } catch (err) {
            setStatus('error');
            setError(err.response?.data?.error ?? 'Échec de la mise à jour.');
        }
    };

    return (
        <Container className="py-4" style={{ maxWidth: '420px' }}>
            <h1>Mon compte</h1>
            <p>Email : {user.email}</p>
            {!user.verified && (
                <div className="alert alert-warning">
                    Votre adresse email n'est pas encore vérifiée. Consultez vos emails pour activer votre compte.
                </div>
            )}

            {error && <div className="alert alert-danger">{error}</div>}
            {'done' === status && <div className="alert alert-success">Mot de passe mis à jour.</div>}

            <h2 className="h5 mt-4">Changer de mot de passe</h2>
            <Form onSubmit={handleSubmit}>
                <Form.Group className="mb-3" controlId="profileCurrentPassword">
                    <Form.Label>Mot de passe actuel</Form.Label>
                    <Form.Control
                        type="password"
                        value={currentPassword}
                        onChange={(e) => setCurrentPassword(e.target.value)}
                        required
                    />
                </Form.Group>
                <Form.Group className="mb-3" controlId="profileNewPassword">
                    <Form.Label>Nouveau mot de passe</Form.Label>
                    <Form.Control
                        type="password"
                        value={newPassword}
                        onChange={(e) => setNewPassword(e.target.value)}
                        minLength={8}
                        required
                    />
                </Form.Group>
                <Button type="submit" variant="primary" disabled={'saving' === status}>
                    Mettre à jour
                </Button>
            </Form>
        </Container>
    );
}
