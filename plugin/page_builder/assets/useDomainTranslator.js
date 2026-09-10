import { useCallback, useEffect, useState } from 'react';

const LOCALE_STORAGE_KEY = 'admLocale';
const LOCALE_EVENT = 'adm-locale-change';

function readLocale() {
    try {
        return window.localStorage.getItem(LOCALE_STORAGE_KEY) || 'fr';
    } catch {
        return 'fr';
    }
}

/**
 * Standalone translator hook for this plugin's own admin UI (step 57):
 * reads/reacts to the admin's shared locale (localStorage + a same-window
 * "adm-locale-change" event dispatched by the admin host's LocaleSwitcher,
 * since a same-tab localStorage write never fires the native "storage"
 * event) and fetches this plugin's own translation catalog domain (step
 * 53's GET /api/i18n/{domain}/{locale}). Deliberately duplicated per
 * plugin (~30 lines) rather than shared via Module Federation - a hook
 * can't be React.lazy()-loaded like a component - which also keeps the
 * plugin fully decoupled from the admin host for i18n.
 *
 * @param {string} domain this plugin's translation domain, e.g. "portfolio"
 */
export default function useDomainTranslator(domain) {
    const [locale, setLocale] = useState(readLocale);
    const [catalog, setCatalog] = useState({});

    useEffect(() => {
        const onChange = (event) => setLocale(event.detail);
        window.addEventListener(LOCALE_EVENT, onChange);

        return () => window.removeEventListener(LOCALE_EVENT, onChange);
    }, []);

    useEffect(() => {
        let cancelled = false;
        fetch(`/api/i18n/${domain}/${locale}`)
            .then((response) => response.json())
            .then((data) => {
                if (!cancelled) {
                    setCatalog(data);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [domain, locale]);

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

    return { t, locale };
}
