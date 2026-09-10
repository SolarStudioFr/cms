import React from 'react';
import { useAuth } from '../auth/AuthContext';
import { useTranslator } from '../i18n/TranslationContext';

export default function Dashboard() {
    const { user, logout } = useAuth();
    const { t } = useTranslator();

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>{t('dashboard.title')}</h1>
                <div>
                    <span className="me-3">{user?.email}</span>
                    <button type="button" className="btn btn-outline-secondary btn-sm" onClick={logout}>
                        {t('dashboard.logout')}
                    </button>
                </div>
            </div>
            <p>{t('dashboard.welcome')}</p>
        </div>
    );
}
