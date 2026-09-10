import React, { useEffect, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from '../api/client';
import MenuItemsEditor from '../components/MenuItemsEditor';
import { useTranslator } from '../i18n/TranslationContext';

/**
 * Create/edit form for a Menu (step 32): name, attachment to one of the
 * active template's declared hooks (see ThemeRegistry, GET /admin/theme/hooks -
 * "Non attaché" leaves it unattached, so it exists without being rendered
 * anywhere yet), and its ordered items (MenuItemsEditor).
 */
export default function MenuForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { t } = useTranslator();
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
            .catch(() => setError(t('menuForm.loadError')))
            .finally(() => setLoading(false));
    }, [id, isEditing, t]);

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
            setError(t('common.saveError'));
        }
    };

    if (loading) {
        return <p>{t('common.loading')}</p>;
    }

    return (
        <div>
            <h1>{isEditing ? t('menuForm.editTitle') : t('menuForm.newTitle')}</h1>

            {error && <div className="alert alert-danger">{error}</div>}

            <Form onSubmit={handleSubmit} style={{ maxWidth: '720px' }}>
                <Form.Group className="mb-3" controlId="menuName">
                    <Form.Label>{t('menuForm.name')}</Form.Label>
                    <Form.Control type="text" value={name} onChange={(e) => setName(e.target.value)} required />
                </Form.Group>

                <Form.Group className="mb-3" controlId="menuHook">
                    <Form.Label>{t('menuForm.hook')}</Form.Label>
                    <Form.Select value={hookName} onChange={(e) => setHookName(e.target.value)}>
                        <option value="">{t('menus.notAttached')}</option>
                        {hooks.map((hook) => (
                            <option key={hook.name} value={hook.name}>
                                {hook.label} ({hook.name})
                            </option>
                        ))}
                    </Form.Select>
                    {0 === hooks.length && (
                        <Form.Text className="text-muted">{t('menuForm.noHooks')}</Form.Text>
                    )}
                </Form.Group>

                <Form.Group className="mb-3" controlId="menuItems">
                    <Form.Label>{t('menuForm.items')}</Form.Label>
                    <MenuItemsEditor items={items} onChange={setItems} />
                </Form.Group>

                <Button type="submit" variant="primary">
                    {t('common.save')}
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/menus')}>
                    {t('common.cancel')}
                </Button>
            </Form>
        </div>
    );
}
