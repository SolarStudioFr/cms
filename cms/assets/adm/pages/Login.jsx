import React, { useState } from 'react';
import { Alert, Button, Card, Form } from 'react-bootstrap';
import { useAuth } from '../auth/AuthContext';
import { useTranslator } from '../i18n/TranslationContext';
import LocaleSwitcher from '../i18n/LocaleSwitcher';

export default function Login() {
    const { login } = useAuth();
    const { t } = useTranslator();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [rememberMe, setRememberMe] = useState(false);
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);

        try {
            await login(email, password, rememberMe);
        } catch {
            setError(t('login.error'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="d-flex justify-content-center align-items-center vh-100 bg-light">
            <div style={{ position: 'absolute', top: '1rem', right: '1rem' }}>
                <LocaleSwitcher />
            </div>
            <Card style={{ width: '24rem' }}>
                <Card.Body>
                    <Card.Title className="mb-4">{t('login.title')}</Card.Title>

                    {error && <Alert variant="danger">{error}</Alert>}

                    <Form onSubmit={handleSubmit}>
                        <Form.Group className="mb-3" controlId="loginEmail">
                            <Form.Label>{t('login.email')}</Form.Label>
                            <Form.Control
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                                autoFocus
                            />
                        </Form.Group>

                        <Form.Group className="mb-3" controlId="loginPassword">
                            <Form.Label>{t('login.password')}</Form.Label>
                            <Form.Control
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                            />
                        </Form.Group>

                        <Form.Group className="mb-3" controlId="loginRememberMe">
                            <Form.Check
                                type="checkbox"
                                label={t('login.rememberMe')}
                                checked={rememberMe}
                                onChange={(e) => setRememberMe(e.target.checked)}
                            />
                        </Form.Group>

                        <Button type="submit" variant="primary" className="w-100" disabled={submitting}>
                            {submitting ? t('login.submitting') : t('login.submit')}
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
}
