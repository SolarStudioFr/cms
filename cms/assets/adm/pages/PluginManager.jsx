import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Table } from 'react-bootstrap';
import client from '../api/client';

/**
 * Plugin manager (step 06): lists every discovered plugin
 * (plugin/*\/plugin.json), with enable/disable and permanent delete.
 * Static admin page (not a plugin itself), built on PluginController.
 */
export default function PluginManager() {
    const [plugins, setPlugins] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/plugins/all')
            .then(({ data }) => setPlugins(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const toggle = async (plugin) => {
        await client.patch(`/admin/plugins/${plugin.name}`, { enabled: !plugin.enabled });
        load();
    };

    const remove = async (plugin) => {
        if (!window.confirm(`Supprimer définitivement le plugin "${plugin.label}" ? Cette action supprime son dossier et ne peut pas être annulée.`)) {
            return;
        }
        await client.delete(`/admin/plugins/${plugin.name}`);
        load();
    };

    return (
        <div>
            <h1 className="mb-4">Plugins</h1>

            {loading ? (
                <p>Chargement...</p>
            ) : plugins.length === 0 ? (
                <p>Aucun plugin détecté.</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {plugins.map((plugin) => (
                            <tr key={plugin.name}>
                                <td>{plugin.label}</td>
                                <td>
                                    <Badge bg={plugin.enabled ? 'success' : 'secondary'}>
                                        {plugin.enabled ? 'Actif' : 'Désactivé'}
                                    </Badge>
                                </td>
                                <td>
                                    <Button
                                        size="sm"
                                        variant={plugin.enabled ? 'outline-warning' : 'outline-success'}
                                        className="me-2"
                                        onClick={() => toggle(plugin)}
                                    >
                                        {plugin.enabled ? 'Désactiver' : 'Activer'}
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(plugin)}>
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
