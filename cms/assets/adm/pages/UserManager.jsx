import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Button, Form, Table } from 'react-bootstrap';
import client from '../api/client';
import { useAuth } from '../auth/AuthContext';

const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

/**
 * Full admin user management (step 28): list every registered user
 * (including those from the public registration form, step 26), create one
 * directly, toggle ROLE_SUPER_ADMIN and the verified flag, delete. Static
 * admin page (not a plugin), built on UserController.
 */
export default function UserManager() {
    const { user: currentUser } = useAuth();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(null);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/users')
            .then(({ data }) => setUsers(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const createUser = async (event) => {
        event.preventDefault();
        setError(null);
        try {
            await client.post('/admin/users', { email, password, roles: [] });
            setEmail('');
            setPassword('');
            load();
        } catch (err) {
            setError(err.response?.data?.error ?? "Échec de la création.");
        }
    };

    const toggleSuperAdmin = async (targetUser) => {
        const roles = targetUser.roles.includes(SUPER_ADMIN)
            ? targetUser.roles.filter((role) => role !== SUPER_ADMIN)
            : [...targetUser.roles, SUPER_ADMIN];
        await client.patch(`/admin/users/${targetUser.id}`, { roles });
        load();
    };

    const toggleVerified = async (targetUser) => {
        await client.patch(`/admin/users/${targetUser.id}`, { verified: !targetUser.verified });
        load();
    };

    const remove = async (targetUser) => {
        if (!window.confirm(`Supprimer l'utilisateur "${targetUser.email}" ?`)) {
            return;
        }
        await client.delete(`/admin/users/${targetUser.id}`);
        load();
    };

    return (
        <div>
            <h1 className="mb-4">Utilisateurs</h1>

            <Form onSubmit={createUser} className="d-flex align-items-end gap-2 mb-4">
                <Form.Group controlId="newUserEmail">
                    <Form.Label>Email</Form.Label>
                    <Form.Control type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                </Form.Group>
                <Form.Group controlId="newUserPassword">
                    <Form.Label>Mot de passe</Form.Label>
                    <Form.Control
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        minLength={8}
                        required
                    />
                </Form.Group>
                <Button type="submit" variant="primary">
                    Créer
                </Button>
            </Form>
            {error && <div className="alert alert-danger">{error}</div>}

            {loading ? (
                <p>Chargement...</p>
            ) : (
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Inscrit le</th>
                            <th>Vérifié</th>
                            <th>Super admin</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.map((targetUser) => (
                            <tr key={targetUser.id}>
                                <td>{targetUser.email}</td>
                                <td>{new Date(targetUser.createdAt).toLocaleDateString()}</td>
                                <td>
                                    <Badge
                                        bg={targetUser.verified ? 'success' : 'secondary'}
                                        role="button"
                                        onClick={() => toggleVerified(targetUser)}
                                    >
                                        {targetUser.verified ? 'Vérifié' : 'Non vérifié'}
                                    </Badge>
                                </td>
                                <td>
                                    <Form.Check
                                        type="switch"
                                        checked={targetUser.roles.includes(SUPER_ADMIN)}
                                        onChange={() => toggleSuperAdmin(targetUser)}
                                    />
                                </td>
                                <td>
                                    <Button
                                        size="sm"
                                        variant="outline-danger"
                                        disabled={targetUser.email === currentUser?.email}
                                        onClick={() => remove(targetUser)}
                                    >
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
