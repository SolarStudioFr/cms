import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider, useAuth } from './auth/AuthContext';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import FileManager from './pages/FileManager';
import PluginManager from './pages/PluginManager';
import Shell from './layout/Shell';
import usePlugins from './plugins/usePlugins';

function AdminRoutes() {
    const { user, loading } = useAuth();
    const { plugins, loading: pluginsLoading } = usePlugins(Boolean(user));

    if (loading) {
        return null;
    }

    if (!user) {
        return <Login />;
    }

    const navItems = plugins.map((plugin) => plugin.navItem).filter(Boolean);
    const pluginRoutes = plugins.flatMap((plugin) => plugin.routes ?? []);

    return (
        <Routes>
            <Route element={<Shell extraNavItems={navItems} />}>
                <Route path="/" element={<Dashboard />} />
                <Route path="/files" element={<FileManager />} />
                <Route path="/plugins" element={<PluginManager />} />
                {!pluginsLoading &&
                    pluginRoutes.map(({ path, element: Element }) => (
                        <Route key={path} path={path} element={<Element />} />
                    ))}
            </Route>
        </Routes>
    );
}

export default function App() {
    return (
        <AuthProvider>
            <BrowserRouter basename="/adm">
                <AdminRoutes />
            </BrowserRouter>
        </AuthProvider>
    );
}
