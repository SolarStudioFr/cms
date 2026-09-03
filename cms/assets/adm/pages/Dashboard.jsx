import React from 'react';
import { useAuth } from '../auth/AuthContext';

export default function Dashboard() {
    const { user, logout } = useAuth();

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Dashboard</h1>
                <div>
                    <span className="me-3">{user?.email}</span>
                    <button type="button" className="btn btn-outline-secondary btn-sm" onClick={logout}>
                        Se déconnecter
                    </button>
                </div>
            </div>
            <p>Bienvenue dans l'administration.</p>
        </div>
    );
}
