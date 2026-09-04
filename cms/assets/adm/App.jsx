import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider, useAuth } from './auth/AuthContext';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import FileManager from './pages/FileManager';
import PluginManager from './pages/PluginManager';
import UserManager from './pages/UserManager';
import SiteConfig from './pages/SiteConfig';
import MenuManager from './pages/MenuManager';
import MenuForm from './pages/MenuForm';
import AdminMenuSettings from './pages/AdminMenuSettings';
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

    // Rendering <Routes> before plugin routes are known would make react-router
    // log a spurious "No routes matched location" warning (and briefly 404) for
    // any direct/deep link into a plugin-owned path (e.g. reloading /adm/pages)
    // - the route table only grows to include it once usePlugins resolves.
    if (pluginsLoading) {
        return <p>Chargement...</p>;
    }

    // Each plugin item is keyed `plugin:<pluginName>` for AdminMenuConfig
    // (step 32 add-on: admin sidebar ordering) to reference it independently
    // of its (French, editable) label.
    const pluginItems = plugins
        .filter((plugin) => plugin.navItem)
        .map((plugin) => ({ key: `plugin:${plugin.pluginName}`, label: plugin.navItem.label, path: plugin.navItem.path }));
    const pluginRoutes = plugins.flatMap((plugin) => plugin.routes ?? []);

    return (
        <Routes>
            <Route element={<Shell pluginItems={pluginItems} />}>
                <Route path="/" element={<Dashboard />} />
                <Route path="/files" element={<FileManager />} />
                <Route path="/plugins" element={<PluginManager />} />
                <Route path="/menus" element={<MenuManager />} />
                <Route path="/menus/new" element={<MenuForm />} />
                <Route path="/menus/:id/edit" element={<MenuForm />} />
                <Route path="/users" element={<UserManager />} />
                <Route path="/settings" element={<SiteConfig />} />
                <Route path="/admin-menu" element={<AdminMenuSettings pluginItems={pluginItems} />} />
                {pluginRoutes.map(({ path, element: Element }) => (
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
