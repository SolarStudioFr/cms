import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import client from '../api/client';

/**
 * Admin interface language (step 54): fetches the full "admin" domain
 * catalog (step 53's GET /api/i18n/{domain}/{locale}) once per locale
 * change and exposes a flat t(key, params?) lookup, since the admin is a
 * React SPA and can't use Twig's trans()/{% trans %}. Locale choice is
 * per-browser (localStorage), independent of the authenticated user and
 * available even on the pre-login screen.
 */
const TranslationContext = createContext(null);

export const AVAILABLE_LOCALES = [
    { code: 'fr', label: 'Français' },
    { code: 'en', label: 'English' },
];

const STORAGE_KEY = 'admLocale';
const DEFAULT_LOCALE = 'fr';

function readStoredLocale() {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        return AVAILABLE_LOCALES.some((l) => l.code === stored) ? stored : DEFAULT_LOCALE;
    } catch {
        return DEFAULT_LOCALE;
    }
}

export function TranslationProvider({ children }) {
    const [locale, setLocaleState] = useState(readStoredLocale);
    const [catalog, setCatalog] = useState({});

    useEffect(() => {
        let cancelled = false;
        client.get(`/i18n/admin/${locale}`).then(({ data }) => {
            if (!cancelled) {
                setCatalog(data);
            }
        });

        return () => {
            cancelled = true;
        };
    }, [locale]);

    const setLocale = useCallback((next) => {
        setLocaleState(next);
        try {
            window.localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // Private browsing / storage disabled: locale just won't persist across reloads.
        }
    }, []);

    /** Looks up a key in the current catalog, interpolating {{placeholder}} values, falling back to the raw key. */
    const t = useCallback(
        (key, params) => {
            const template = catalog[key] ?? key;
            if (!params) {
                return template;
            }

            return Object.entries(params).reduce(
                (text, [name, value]) => text.replaceAll(`{{${name}}}`, String(value)),
                template,
            );
        },
        [catalog],
    );

    const value = useMemo(() => ({ t, locale, setLocale }), [t, locale, setLocale]);

    return <TranslationContext.Provider value={value}>{children}</TranslationContext.Provider>;
}

export function useTranslator() {
    const context = useContext(TranslationContext);
    if (!context) {
        throw new Error('useTranslator() must be used within a TranslationProvider');
    }

    return context;
}
