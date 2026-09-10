import React, { useEffect, useState } from 'react';
import { Container } from 'react-bootstrap';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';
import { useTranslator } from './i18n/TranslationContext';

export default function VerifyEmail() {
    const { token } = useParams();
    const { t } = useTranslator();
    const [status, setStatus] = useState('checking'); // checking | success | error

    useEffect(() => {
        client
            .get(`/verify-email/${token}`)
            .then(() => setStatus('success'))
            .catch(() => setStatus('error'));
    }, [token]);

    return (
        <Container className="py-4">
            <h1>{t('verifyEmail.title')}</h1>
            {'checking' === status && <p>{t('verifyEmail.checking')}</p>}
            {'success' === status && (
                <p className="text-success">
                    {t('verifyEmail.success')} <Link to="/login">{t('verifyEmail.signIn')}</Link>.
                </p>
            )}
            {'error' === status && <p className="text-danger">{t('verifyEmail.error')}</p>}
        </Container>
    );
}
