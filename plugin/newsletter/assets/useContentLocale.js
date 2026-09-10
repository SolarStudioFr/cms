import { useEffect, useState } from 'react';
import client from './api/client';

/**
 * Drives the "edit this content in another language" experience (step 58
 * follow-up): a language switcher swaps the *entire* form between the
 * base (default-language) fields and a translation, rather than a separate
 * side panel - the same fields, the same layout, just bound to different
 * underlying data depending on which language tab is active. A locale not
 * yet translated shows a copy of the base values ("copie conforme") until
 * edited. Duplicated per plugin (same as useDomainTranslator.js, step 57) -
 * a hook can't be Module-Federation-lazy-loaded like a component.
 *
 * The base/default-language fields stay in the calling form's own
 * `useState` (e.g. `title`, `contentValue`) exactly as before this
 * mechanism existed; this hook only adds a second store, `translations`,
 * for every *other* language, plus `fieldValue`/`setField` helpers that
 * route a field's displayed value/onChange to the right store depending on
 * `isDefaultLocale`.
 *
 * @param {string} entityType e.g. "portfolio_item"
 * @param {number|null} entityId null before the entity has ever been saved - translations are only fetched once it's real
 */
export default function useContentLocale(entityType, entityId) {
    const [activeLangs, setActiveLangs] = useState([]);
    const [defaultLocale, setDefaultLocale] = useState(null);
    const [editingLocale, setEditingLocale] = useState(null);
    const [translations, setTranslations] = useState({}); // { [locale]: { [field]: value } }

    useEffect(() => {
        client.get('/langs').then(({ data }) => setActiveLangs(data));
        client.get('/site-config').then(({ data }) => setDefaultLocale(data.defaultLocale || null));
    }, []);

    useEffect(() => {
        if (null === entityId) {
            return;
        }
        client
            .get('/admin/content-translations', { params: { entityType, entityId } })
            .then(({ data }) => setTranslations(data));
    }, [entityType, entityId]);

    // No configured default language: fall back to the first active one, so
    // there's still always exactly one "base" tab (same convention as the
    // public/admin TranslationContext's own locale fallback).
    const effectiveDefaultLocale = defaultLocale || activeLangs[0]?.code || null;

    useEffect(() => {
        if (!editingLocale && effectiveDefaultLocale) {
            setEditingLocale(effectiveDefaultLocale);
        }
    }, [effectiveDefaultLocale, editingLocale]);

    const isDefaultLocale = editingLocale === effectiveDefaultLocale;

    /** The value shown for one field: the base value on the default-language tab, otherwise this locale's own edit (falling back to a copy of the base value). */
    const fieldValue = (baseValue, key) => (isDefaultLocale ? baseValue : (translations[editingLocale]?.[key] ?? baseValue));

    /** Returns an onChange-ready setter for one field, routed to the base state setter or to this locale's own translation store depending on the active tab. */
    const setField = (key, setBaseValue) => (value) => {
        if (isDefaultLocale) {
            setBaseValue(value);
        } else {
            setTranslations((current) => ({
                ...current,
                [editingLocale]: { ...(current[editingLocale] ?? {}), [key]: value },
            }));
        }
    };

    /**
     * Persists every language other than the default one that has ever been
     * touched (including a locale whose fields never actually diverged from
     * the base - "copie conforme" always writes a real standalone copy, not
     * a sparse patch), called once right after the base entity itself has
     * been saved.
     *
     * @param {number} savedEntityId the just-created/updated entity's real id
     * @param {Object<string, string>} baseFieldValues current values of every translatable field, as just saved on the base entity - the fallback for any locale that doesn't override a given field
     * @param {string|null} [builderFieldName] when set (e.g. "builderData"), also derives a translated "content" HTML value via page_builder/renderToHtml
     */
    const saveAllTranslations = async (savedEntityId, baseFieldValues, builderFieldName = null) => {
        for (const locale of Object.keys(translations)) {
            const values = {};
            for (const [key, baseValue] of Object.entries(baseFieldValues)) {
                values[key] = translations[locale]?.[key] ?? baseValue;
            }
            if (builderFieldName) {
                const { default: renderToHtml } = await import('page_builder/renderToHtml');
                values.content = renderToHtml(values[builderFieldName] || '');
            }
            await client.patch('/admin/content-translations', { entityType, entityId: savedEntityId, locale, values });
        }
    };

    return { activeLangs, editingLocale, setEditingLocale, isDefaultLocale, fieldValue, setField, saveAllTranslations };
}
