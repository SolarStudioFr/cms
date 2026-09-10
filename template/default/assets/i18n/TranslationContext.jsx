import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import client from '../api/client';

/**
 * Public interface language (step 56): fetches the "public" domain catalog
 * (step 53's GET /api/i18n/{domain}/{locale}) once per locale change and
 * exposes a flat t(key, params?) lookup, since the public site is a React
 * SPA and can't use Twig's trans()/{% trans %}. Also fetches the site's
 * active languages (step 55, GET /api/langs) - both to validate/default the
 * chosen locale and to feed the language switcher (LanguageSwitcher.jsx),
 * so both only need a single shared fetch.
 *
 * The chosen locale here doubles as the *content* locale from step 58 (the
 * user's explicit choice: switching the interface language also switches
 * which content translation is shown) - `locale` is the one value both
 * mechanisms read.
 */
const TranslationContext = createContext(null);

const STORAGE_KEY = 'publicLocale';
const FALLBACK_LOCALE = 'fr';

function readStoredLocale() {
    try {
        return window.localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function writeStoredLocale(locale) {
    try {
        window.localStorage.setItem(STORAGE_KEY, locale);
    } catch {
        // Private browsing / storage disabled: locale just won't persist across reloads.
    }
}

export function TranslationProvider({ children }) {
    const [activeLangs, setActiveLangs] = useState([]);
    const [locale, setLocaleState] = useState(readStoredLocale() ?? FALLBACK_LOCALE);
    const [catalog, setCatalog] = useState({});

    useEffect(() => {
        client.get('/langs').then(({ data }) => {
            setActiveLangs(data);
            const codes = data.map((lang) => lang.code);
            if (0 === codes.length) {
                return;
            }
            setLocaleState((current) => (codes.includes(current) ? current : codes.includes(FALLBACK_LOCALE) ? FALLBACK_LOCALE : codes[0]));
        });
    }, []);

    useEffect(() => {
        let cancelled = false;
        client.get(`/i18n/public/${locale}`).then(({ data }) => {
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
        writeStoredLocale(next);
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

    const value = useMemo(() => ({ t, locale, setLocale, activeLangs }), [t, locale, setLocale, activeLangs]);

    return <TranslationContext.Provider value={value}>{children}</TranslationContext.Provider>;
}

export function useTranslator() {
    const context = useContext(TranslationContext);
    if (!context) {
        throw new Error('useTranslator() must be used within a TranslationProvider');
    }

    return context;
}
