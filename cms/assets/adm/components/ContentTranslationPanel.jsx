import React, { Suspense, useEffect, useState } from 'react';
import { Button, Card, Form, Nav, Tab } from 'react-bootstrap';
import client from '../api/client';
import RichTextEditor from './RichTextEditor';
import { useTranslator } from '../i18n/TranslationContext';

/**
 * Shared content-language editor (step 58): lets an admin fill in one or
 * more content fields (title, content, SEO...) for every site language
 * other than the default one, storing them in the generic
 * ContentTranslation overlay (App\Entity\ContentTranslation) rather than a
 * table per content type. Exposed by the admin host via Module Federation
 * (same mechanism as MediaPicker/RichTextEditor, step 09) so Page's own
 * form (core, direct import) and every content plugin's form
 * (Portfolio/News/Homepage, `adm_host/ContentTranslationPanel`) can embed
 * it without knowing anything about how translations are stored - i18n
 * itself never imports or references any of them back (this component only
 * ever receives a caller-chosen `entityType` string).
 *
 * @param {string} entityType e.g. "page", "portfolio_item", "news_article", "homepage"
 * @param {number|null} entityId null before the entity has ever been saved - translating requires a real id
 * @param {Array<{name: string, label: string, type?: 'text'|'textarea'|'html'}>} fields
 */
export default function ContentTranslationPanel({ entityType, entityId, fields }) {
    const { t } = useTranslator();
    const [langs, setLangs] = useState([]);
    const [defaultLocale, setDefaultLocale] = useState(null);
    const [translations, setTranslations] = useState({}); // { [locale]: { [field]: value } }
    const [activeLocale, setActiveLocale] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saved, setSaved] = useState(false);

    useEffect(() => {
        if (null === entityId) {
            setLoading(false);
            return;
        }

        Promise.all([
            client.get('/admin/langs'),
            client.get('/site-config'),
            client.get('/admin/content-translations', { params: { entityType, entityId } }),
        ]).then(([langsResponse, siteConfigResponse, translationsResponse]) => {
            setLangs(langsResponse.data);
            setDefaultLocale(siteConfigResponse.data.defaultLocale || null);
            setTranslations(translationsResponse.data);
            setLoading(false);
        });
    }, [entityType, entityId]);

    const translatableLangs = langs.filter((lang) => lang.code !== defaultLocale);

    useEffect(() => {
        if (!activeLocale && translatableLangs.length > 0) {
            setActiveLocale(translatableLangs[0].code);
        }
    }, [translatableLangs, activeLocale]);

    const setFieldValue = (locale, field, value) => {
        setTranslations((current) => ({
            ...current,
            [locale]: { ...(current[locale] ?? {}), [field]: value },
        }));
    };

    const save = async () => {
        setSaved(false);
        const values = translations[activeLocale] ?? {};
        await client.patch('/admin/content-translations', { entityType, entityId, locale: activeLocale, values });
        setSaved(true);
        setTimeout(() => setSaved(false), 2000);
    };

    if (null === entityId) {
        return (
            <Card className="mb-3">
                <Card.Header>{t('contentTranslation.title')}</Card.Header>
                <Card.Body>
                    <p className="text-muted small mb-0">{t('contentTranslation.saveFirst')}</p>
                </Card.Body>
            </Card>
        );
    }

    if (loading) {
        return (
            <Card className="mb-3">
                <Card.Header>{t('contentTranslation.title')}</Card.Header>
                <Card.Body>
                    <p className="text-muted small mb-0">{t('common.loading')}</p>
                </Card.Body>
            </Card>
        );
    }

    if (0 === translatableLangs.length) {
        return (
            <Card className="mb-3">
                <Card.Header>{t('contentTranslation.title')}</Card.Header>
                <Card.Body>
                    <p className="text-muted small mb-0">{t('contentTranslation.noOtherLanguage')}</p>
                </Card.Body>
            </Card>
        );
    }

    const values = translations[activeLocale] ?? {};

    return (
        <Card className="mb-3">
            <Card.Header>{t('contentTranslation.title')}</Card.Header>
            <Card.Body>
                <Tab.Container activeKey={activeLocale} onSelect={setActiveLocale}>
                    <Nav variant="tabs" className="mb-3">
                        {translatableLangs.map((lang) => (
                            <Nav.Item key={lang.code}>
                                <Nav.Link eventKey={lang.code}>{lang.label}</Nav.Link>
                            </Nav.Item>
                        ))}
                    </Nav>
                </Tab.Container>

                {fields.map((field) => (
                    <Form.Group className="mb-3" key={field.name}>
                        <Form.Label>{field.label}</Form.Label>
                        {'html' === field.type ? (
                            <Suspense fallback={<p className="text-muted small mb-0">{t('common.loading')}</p>}>
                                <RichTextEditor
                                    value={values[field.name] ?? ''}
                                    onChange={(html) => setFieldValue(activeLocale, field.name, html)}
                                />
                            </Suspense>
                        ) : (
                            <Form.Control
                                as={'textarea' === field.type ? 'textarea' : 'input'}
                                rows={'textarea' === field.type ? 3 : undefined}
                                value={values[field.name] ?? ''}
                                onChange={(event) => setFieldValue(activeLocale, field.name, event.target.value)}
                            />
                        )}
                    </Form.Group>
                ))}

                {saved && <div className="alert alert-success py-1 px-2 small">{t('contentTranslation.saved')}</div>}

                <Button size="sm" variant="outline-primary" onClick={save}>
                    {t('contentTranslation.save')}
                </Button>
            </Card.Body>
        </Card>
    );
}
