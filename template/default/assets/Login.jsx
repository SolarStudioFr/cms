import React, { useState } from 'react';
import { Button, Container, Form } from 'react-bootstrap';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from './auth/AuthContext';
import { useTranslator } from './i18n/TranslationContext';

export default function Login() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const { t } = useTranslator();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(null);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);
        try {
            await login(email, password);
            navigate('/profile');
        } catch {
            setError(t('login.error'));
        }
    };

    return (
        <Container className="py-4" style={{ maxWidth: '420px' }}>
            <h1>{t('login.title')}</h1>
            {error && <div className="alert alert-danger">{error}</div>}
            <Form onSubmit={handleSubmit}>
                <Form.Group className="mb-3" controlId="loginEmail">
                    <Form.Label>{t('login.email')}</Form.Label>
                    <Form.Control type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
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
                <Button type="submit" variant="primary">
                    {t('login.submit')}
                </Button>
            </Form>
            <p className="mt-3">
                {t('login.noAccount')} <Link to="/register">{t('login.signUp')}</Link>
            </p>
        </Container>
    );
}
