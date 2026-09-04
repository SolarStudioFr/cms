import React, { useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import client from './api/client';

/**
 * Public newsletter signup form (step 25), shown in the site footer on
 * every page. POSTs to the plugin's public endpoint (step 23's Subscriber
 * resource) - re-submitting an already-subscribed email is treated as a
 * success (see SubscriberSignupProcessor), so the UI never needs to
 * distinguish "new" from "already subscribed".
 */
export default function NewsletterSignup() {
    const [email, setEmail] = useState('');
    const [status, setStatus] = useState('idle'); // idle | sending | done | error

    const handleSubmit = async (event) => {
        event.preventDefault();
        setStatus('sending');

        try {
            await client.post('/newsletter/subscribers', { email });
            setStatus('done');
            setEmail('');
        } catch {
            setStatus('error');
        }
    };

    return (
        <Form onSubmit={handleSubmit} className="d-flex align-items-center flex-wrap gap-2">
            <Form.Control
                type="email"
                placeholder="Votre email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                style={{ maxWidth: '260px' }}
            />
            <Button type="submit" variant="outline-light" size="sm" disabled={'sending' === status}>
                S'inscrire à la newsletter
            </Button>
            {'done' === status && <span className="text-success small">Inscription réussie.</span>}
            {'error' === status && <span className="text-danger small">Échec de l'inscription.</span>}
        </Form>
    );
}
