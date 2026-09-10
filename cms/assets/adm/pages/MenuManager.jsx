import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import client from '../api/client';
import { useTranslator } from '../i18n/TranslationContext';

/**
 * Menu manager (step 32): lists every admin-defined menu, whether or not
 * it's currently attached to a template hook. Static admin page (not a
 * plugin), same shape as PluginManager/FileManager.
 */
export default function MenuManager() {
    const { t } = useTranslator();
    const [menus, setMenus] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/menus')
            .then(({ data }) => setMenus(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const remove = async (menu) => {
        if (!window.confirm(t('menus.confirmDelete', { name: menu.name }))) {
            return;
        }
        await client.delete(`/admin/menus/${menu.id}`);
        load();
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>{t('menus.title')}</h1>
                <Button as={Link} to="/menus/new" variant="primary">
                    {t('menus.create')}
                </Button>
            </div>

            {loading ? (
                <p>{t('common.loading')}</p>
            ) : menus.length === 0 ? (
                <p>{t('menus.none')}</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>{t('menus.name')}</th>
                            <th>{t('menus.hook')}</th>
                            <th>{t('menus.items')}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {menus.map((menu) => (
                            <tr key={menu.id}>
                                <td>{menu.name}</td>
                                <td>
                                    {menu.hookName ? (
                                        <Badge bg="success">{menu.hookName}</Badge>
                                    ) : (
                                        <Badge bg="secondary">{t('menus.notAttached')}</Badge>
                                    )}
                                </td>
                                <td>{menu.items.length}</td>
                                <td>
                                    <Button
                                        as={Link}
                                        to={`/menus/${menu.id}/edit`}
                                        size="sm"
                                        variant="outline-secondary"
                                        className="me-2"
                                    >
                                        {t('common.edit')}
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(menu)}>
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
