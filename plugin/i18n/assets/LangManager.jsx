import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Form, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from './api/client';

/**
 * Admin language manager (step 07): lists every Lang, lets the admin add
 * one, toggle it active/inactive, or delete it. Simple enough (2 editable
 * fields + a flag) that it doesn't need a separate create/edit route like
 * the Page plugin's PageForm.
 */
export default function LangManager() {
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
            setError("Échec de l'ajout (code déjà utilisé ?).");
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
        if (!window.confirm(`Supprimer la langue "${lang.label}" ?`)) {
            return;
        }
        await client.delete(`/admin/langs/${lang.id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Langues</h1>
                <Button as={Link} to="/translations" variant="outline-secondary">
                    Traductions
                </Button>
            </div>

            <Form onSubmit={addLang} className="d-flex align-items-end gap-2 mb-4">
                <Form.Group controlId="langCode">
                    <Form.Label>Code</Form.Label>
                    <Form.Control
                        value={code}
                        onChange={(e) => setCode(e.target.value)}
                        placeholder="ex. de"
                        maxLength={10}
                        required
                        style={{ width: '100px' }}
                    />
                </Form.Group>
                <Form.Group controlId="langLabel">
                    <Form.Label>Nom</Form.Label>
                    <Form.Control
                        value={label}
                        onChange={(e) => setLabel(e.target.value)}
                        placeholder="ex. Deutsch"
                        required
                        style={{ width: '200px' }}
                    />
                </Form.Group>
                <Button type="submit" variant="primary">
                    Ajouter
                </Button>
            </Form>

            {error && <div className="alert alert-danger">{error}</div>}

            {loading ? (
                <p>Chargement...</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Statut</th>
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
                                        {lang.active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </td>
                                <td>
                                    <Button
                                        size="sm"
                                        variant={lang.active ? 'outline-warning' : 'outline-success'}
                                        className="me-2"
                                        onClick={() => toggleActive(lang)}
                                    >
                                        {lang.active ? 'Désactiver' : 'Activer'}
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(lang)}>
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
