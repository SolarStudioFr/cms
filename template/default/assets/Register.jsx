import React, { useState } from 'react';
import { Button, Container, Form } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';

/**
 * Public registration form (step 26). Doesn't log the visitor in directly
 * after submitting - they still need to click the verification link sent
 * by email, consistent with `User::isVerified()`'s intent.
 */
export default function Register() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [status, setStatus] = useState('idle'); // idle | sending | done | error
    const [error, setError] = useState(null);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setStatus('sending');
        setError(null);

        try {
            await client.post('/register', { email, password });
            setStatus('done');
        } catch (err) {
            setStatus('error');
            setError(err.response?.data?.error ?? "Échec de l'inscription.");
        }
    };

    if ('done' === status) {
        return (
            <Container className="py-4" style={{ maxWidth: '420px' }}>
                <h1>Inscription</h1>
                <p className="text-success">
                    Un email de vérification a été envoyé à {email}. Cliquez sur le lien qu'il contient pour activer
                    votre compte.
                </p>
            </Container>
        );
    }

    return (
        <Container className="py-4" style={{ maxWidth: '420px' }}>
            <h1>Inscription</h1>
            {error && <div className="alert alert-danger">{error}</div>}
            <Form onSubmit={handleSubmit}>
                <Form.Group className="mb-3" controlId="registerEmail">
                    <Form.Label>Email</Form.Label>
                    <Form.Control type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                </Form.Group>
                <Form.Group className="mb-3" controlId="registerPassword">
                    <Form.Label>Mot de passe</Form.Label>
                    <Form.Control
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        minLength={8}
                        required
                    />
                </Form.Group>
                <Button type="submit" variant="primary" disabled={'sending' === status}>
                    S'inscrire
                </Button>
            </Form>
            <p className="mt-3">
                Déjà inscrit ? <Link to="/login">Se connecter</Link>
            </p>
        </Container>
    );
}
