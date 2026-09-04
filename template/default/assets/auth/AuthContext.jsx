import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import client from '../api/client';

/**
 * Public-site counterpart of cms/assets/adm/auth/AuthContext.jsx (step 26) -
 * same session-cookie endpoints (/login, /logout, /me), since json_login
 * isn't scoped to the admin: any registered User can authenticate through
 * it, admin or not.
 */
const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    const refresh = useCallback(async () => {
        try {
            const { data } = await client.get('/me');
            setUser(data);
        } catch {
            setUser(null);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        refresh();
    }, [refresh]);

    const login = useCallback(async (email, password) => {
        const { data } = await client.post('/login', { email, password });
        setUser(data);

        return data;
    }, []);

    const logout = useCallback(async () => {
        await client.get('/logout').catch(() => {});
        setUser(null);
    }, []);

    return (
        <AuthContext.Provider value={{ user, loading, login, logout, refresh }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}
