import React, { useState } from 'react';
import { Button, Container, Form } from 'react-bootstrap';
import { Navigate } from 'react-router-dom';
import { useAuth } from './auth/AuthContext';
import client from './api/client';
import { useTranslator } from './i18n/TranslationContext';

/**
 * Member profile/account editing (step 27). Password change only - email
 * is the login identifier, changing it would need its own re-verification
 * flow (not asked for), so it's shown read-only here.
 */
export default function Profile() {
    const { user, loading, refresh } = useAuth();
    const { t } = useTranslator();
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
            setError(err.response?.data?.error ?? t('profile.updateError'));
        }
    };

    return (
        <Container className="py-4" style={{ maxWidth: '420px' }}>
            <h1>{t('profile.title')}</h1>
            <p>{t('profile.emailLabel')} {user.email}</p>
            {!user.verified && (
                <div className="alert alert-warning">{t('profile.notVerified')}</div>
            )}

            {error && <div className="alert alert-danger">{error}</div>}
            {'done' === status && <div className="alert alert-success">{t('profile.updateSuccess')}</div>}

            <h2 className="h5 mt-4">{t('profile.changePassword')}</h2>
            <Form onSubmit={handleSubmit}>
                <Form.Group className="mb-3" controlId="profileCurrentPassword">
                    <Form.Label>{t('profile.currentPassword')}</Form.Label>
                    <Form.Control
                        type="password"
                        value={currentPassword}
                        onChange={(e) => setCurrentPassword(e.target.value)}
                        required
                    />
                </Form.Group>
                <Form.Group className="mb-3" controlId="profileNewPassword">
                    <Form.Label>{t('profile.newPassword')}</Form.Label>
                    <Form.Control
                        type="password"
                        value={newPassword}
                        onChange={(e) => setNewPassword(e.target.value)}
                        minLength={8}
                        required
                    />
                </Form.Group>
                <Button type="submit" variant="primary" disabled={'saving' === status}>
                    {t('profile.submit')}
                </Button>
            </Form>
        </Container>
    );
}
