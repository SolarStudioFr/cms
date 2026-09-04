import React, { useEffect, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from '../api/client';
import MenuItemsEditor from '../components/MenuItemsEditor';

/**
 * Create/edit form for a Menu (step 32): name, attachment to one of the
 * active template's declared hooks (see ThemeRegistry, GET /admin/theme/hooks -
 * "Non attaché" leaves it unattached, so it exists without being rendered
 * anywhere yet), and its ordered items (MenuItemsEditor).
 */
export default function MenuForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const isEditing = Boolean(id);

    const [name, setName] = useState('');
    const [hookName, setHookName] = useState('');
    const [items, setItems] = useState([]);
    const [hooks, setHooks] = useState([]);
    const [loading, setLoading] = useState(isEditing);
    const [error, setError] = useState(null);

    useEffect(() => {
        client.get('/admin/theme/hooks').then(({ data }) => setHooks(data));
    }, []);

    useEffect(() => {
        if (!isEditing) {
            return;
        }
        client
            .get(`/admin/menus/${id}`)
            .then(({ data }) => {
                setName(data.name);
                setHookName(data.hookName ?? '');
                setItems(data.items);
            })
            .catch(() => setError('Impossible de charger le menu.'))
            .finally(() => setLoading(false));
    }, [id, isEditing]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);

        const payload = { name, hookName: hookName || null, items };

        try {
            if (isEditing) {
                await client.patch(`/admin/menus/${id}`, payload, {
                    headers: { 'Content-Type': 'application/merge-patch+json' },
                });
            } else {
                await client.post('/admin/menus', payload);
            }
            navigate('/menus');
        } catch {
            setError("Échec de l'enregistrement.");
        }
    };

    if (loading) {
        return <p>Chargement...</p>;
    }

    return (
        <div>
            <h1>{isEditing ? 'Modifier le menu' : 'Nouveau menu'}</h1>

            {error && <div className="alert alert-danger">{error}</div>}

            <Form onSubmit={handleSubmit} style={{ maxWidth: '720px' }}>
                <Form.Group className="mb-3" controlId="menuName">
                    <Form.Label>Nom</Form.Label>
                    <Form.Control type="text" value={name} onChange={(e) => setName(e.target.value)} required />
                </Form.Group>

                <Form.Group className="mb-3" controlId="menuHook">
                    <Form.Label>Attaché au hook</Form.Label>
                    <Form.Select value={hookName} onChange={(e) => setHookName(e.target.value)}>
                        <option value="">Non attaché</option>
                        {hooks.map((hook) => (
                            <option key={hook.name} value={hook.name}>
                                {hook.label} ({hook.name})
                            </option>
                        ))}
                    </Form.Select>
                    {0 === hooks.length && (
                        <Form.Text className="text-muted">
                            Le thème actif ne déclare aucun hook (template/default/theme.json).
                        </Form.Text>
                    )}
                </Form.Group>

                <Form.Group className="mb-3" controlId="menuItems">
                    <Form.Label>Éléments</Form.Label>
                    <MenuItemsEditor items={items} onChange={setItems} />
                </Form.Group>

                <Button type="submit" variant="primary">
                    Enregistrer
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/menus')}>
                    Annuler
                </Button>
            </Form>
        </div>
    );
}
