import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Form, Table } from 'react-bootstrap';
import client from '../api/client';
import { useTranslator } from '../i18n/TranslationContext';

/**
 * Available/active site languages (step 55): folded into Configuration
 * rather than kept as its own admin page (step 07's LangManager, now
 * removed) - the user's explicit choice was one combined UI rather than
 * two ("add/remove a language" and "toggle its public activation" used to
 * be conceptually separate concerns but are shown together here). Still
 * backed by the same Lang entity/`/admin/langs` endpoints from step 07;
 * `active` already meant "listed by the public GET /api/langs" before this
 * step (see ActiveLangCollectionProvider) - this section is the only UI
 * left that can flip it.
 */
export default function LanguagesSection() {
    const { t } = useTranslator();
    const [langs, setLangs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [code, setCode] = useState('');
    const [label, setLabel] = useState('');
    const [error, setError] = useState(null);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/langs')
            .then(({ data }) => setLangs(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const addLang = async (event) => {
        event.preventDefault();
        setError(null);
        try {
            await client.post('/admin/langs', { code, label, active: true });
            setCode('');
            setLabel('');
            load();
        } catch {
            setError(t('languages.addError'));
        }
    };

    const toggleActive = async (lang) => {
        await client.patch(
            `/admin/langs/${lang.id}`,
            { active: !lang.active },
            { headers: { 'Content-Type': 'application/merge-patch+json' } },
        );
        load();
    };

    const remove = async (lang) => {
        if (!window.confirm(t('languages.confirmDelete', { label: lang.label }))) {
            return;
        }
        await client.delete(`/admin/langs/${lang.id}`);
        load();
    };

    return (
        <div>
            <h2 className="h5">{t('languages.title')}</h2>
            <p className="text-muted small">{t('languages.description')}</p>

            <Form onSubmit={addLang} className="d-flex align-items-end gap-2 mb-3">
                <Form.Group controlId="langCode">
                    <Form.Label>{t('languages.code')}</Form.Label>
                    <Form.Control
                        value={code}
                        onChange={(e) => setCode(e.target.value)}
                        placeholder={t('languages.codePlaceholder')}
                        maxLength={10}
                        required
                        style={{ width: '100px' }}
                    />
                </Form.Group>
                <Form.Group controlId="langLabel">
                    <Form.Label>{t('languages.name')}</Form.Label>
                    <Form.Control
                        value={label}
                        onChange={(e) => setLabel(e.target.value)}
                        placeholder={t('languages.namePlaceholder')}
                        required
                        style={{ width: '200px' }}
                    />
                </Form.Group>
                <Button type="submit" variant="outline-primary">
                    {t('common.add')}
                </Button>
            </Form>

            {error && <div className="alert alert-danger">{error}</div>}

            {loading ? (
                <p>{t('common.loading')}</p>
            ) : (
                <Table striped bordered hover style={{ maxWidth: '640px' }}>
                    <thead>
                        <tr>
                            <th>{t('languages.code')}</th>
                            <th>{t('languages.name')}</th>
                            <th>{t('languages.publicStatus')}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {langs.map((lang) => (
                            <tr key={lang.id}>
                                <td>{lang.code}</td>
                                <td>{lang.label}</td>
                                <td>
                                    <Badge bg={lang.active ? 'success' : 'secondary'}>
                                        {lang.active ? t('languages.active') : t('languages.inactive')}
                                    </Badge>
                                </td>
                                <td>
                                    <Button
                                        size="sm"
                                        variant={lang.active ? 'outline-warning' : 'outline-success'}
                                        className="me-2"
                                        onClick={() => toggleActive(lang)}
                                    >
                                        {lang.active ? t('languages.deactivate') : t('languages.activate')}
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(lang)}>
                                        {t('common.delete')}
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            )}
        </div>
    );
}
