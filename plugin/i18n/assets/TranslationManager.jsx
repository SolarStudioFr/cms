import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Form, Table } from 'react-bootstrap';
import client from './api/client';

/**
 * Admin translation editor (step 08): pick a language + domain, then
 * list/add/edit/delete its key/value translations, with PO export/import.
 */
export default function TranslationManager() {
    const [langs, setLangs] = useState([]);
    const [langCode, setLangCode] = useState('');
    const [domain, setDomain] = useState('messages');
    const [translations, setTranslations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [newKey, setNewKey] = useState('');
    const [newValue, setNewValue] = useState('');
    const [notice, setNotice] = useState(null);
    const fileInputRef = useRef(null);

    useEffect(() => {
        client.get('/admin/langs').then(({ data }) => {
            setLangs(data);
            if (data.length > 0) {
                setLangCode((current) => current || data[0].code);
            }
        });
    }, []);

    const load = useCallback(() => {
        if (!langCode) {
            return;
        }
        setLoading(true);
        client
            .get('/admin/translations', { params: { lang: langCode, domain } })
            .then(({ data }) => setTranslations(data))
            .finally(() => setLoading(false));
    }, [langCode, domain]);

    useEffect(() => {
        load();
    }, [load]);

    const saveValue = async (key, value) => {
        await client.post('/admin/translations', { lang: langCode, domain, key, value });
    };

    const updateValue = (id, value) => {
        setTranslations((current) => current.map((t) => (t.id === id ? { ...t, value } : t)));
    };

    const persistValue = async (translation) => {
        await saveValue(translation.key, translation.value);
    };

    const addTranslation = async (event) => {
        event.preventDefault();
        if (!newKey) {
            return;
        }
        await saveValue(newKey, newValue);
        setNewKey('');
        setNewValue('');
        load();
    };

    const remove = async (translation) => {
        if (!window.confirm(`Supprimer la traduction "${translation.key}" ?`)) {
            return;
        }
        await client.delete(`/admin/translations/${translation.id}`);
        load();
    };

    const exportPo = async () => {
        const { data } = await client.get('/admin/translations/export', {
            params: { lang: langCode, domain },
            responseType: 'blob',
        });
        const url = URL.createObjectURL(data);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${domain}.${langCode}.po`;
        link.click();
        URL.revokeObjectURL(url);
    };

    const importPo = async (event) => {
        event.preventDefault();
        const input = fileInputRef.current;
        if (!input?.files?.length) {
            return;
        }

        const formData = new FormData();
        formData.append('file', input.files[0]);
        formData.append('lang', langCode);
        formData.append('domain', domain);

        const { data } = await client.post('/admin/translations/import', formData);
        input.value = '';
        setNotice(`${data.imported} traduction(s) importée(s).`);
        load();
    };

    return (
        <div>
            <h1 className="mb-4">Traductions</h1>

            <div className="d-flex align-items-end gap-3 mb-4">
                <Form.Group controlId="translationLang">
                    <Form.Label>Langue</Form.Label>
                    <Form.Select value={langCode} onChange={(e) => setLangCode(e.target.value)} style={{ width: '160px' }}>
                        {langs.map((lang) => (
                            <option key={lang.code} value={lang.code}>
                                {lang.label} ({lang.code})
                            </option>
                        ))}
                    </Form.Select>
                </Form.Group>
                <Form.Group controlId="translationDomain">
                    <Form.Label>Domaine</Form.Label>
                    <Form.Control value={domain} onChange={(e) => setDomain(e.target.value)} style={{ width: '160px' }} />
                </Form.Group>
                <Button variant="outline-secondary" onClick={exportPo}>
                    Exporter (.po)
                </Button>
                <Form onSubmit={importPo} className="d-flex align-items-center gap-2">
                    <Form.Control type="file" ref={fileInputRef} accept=".po" style={{ width: '220px' }} />
                    <Button type="submit" variant="outline-secondary">
                        Importer
                    </Button>
                </Form>
            </div>

            {notice && <div className="alert alert-info">{notice}</div>}

            <Form onSubmit={addTranslation} className="d-flex align-items-end gap-2 mb-4">
                <Form.Group controlId="newTranslationKey">
                    <Form.Label>Clé</Form.Label>
                    <Form.Control value={newKey} onChange={(e) => setNewKey(e.target.value)} required style={{ width: '220px' }} />
                </Form.Group>
                <Form.Group controlId="newTranslationValue">
                    <Form.Label>Valeur</Form.Label>
                    <Form.Control value={newValue} onChange={(e) => setNewValue(e.target.value)} style={{ width: '320px' }} />
                </Form.Group>
                <Button type="submit" variant="primary">
                    Ajouter
                </Button>
            </Form>

            {loading ? (
                <p>Chargement...</p>
            ) : translations.length === 0 ? (
                <p>Aucune traduction pour cette langue/domaine.</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>Clé</th>
                            <th>Valeur</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {translations.map((translation) => (
                            <tr key={translation.id}>
                                <td className="align-middle">{translation.key}</td>
                                <td>
                                    <Form.Control
                                        value={translation.value}
                                        onChange={(e) => updateValue(translation.id, e.target.value)}
                                        onBlur={() => persistValue(translation)}
                                    />
                                </td>
                                <td className="align-middle">
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(translation)}>
                                        Supprimer
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
