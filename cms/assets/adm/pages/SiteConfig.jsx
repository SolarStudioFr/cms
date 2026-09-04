import React, { useEffect, useState } from 'react';
import { Button, Form, Image } from 'react-bootstrap';
import client from '../api/client';
import MediaPicker from '../components/MediaPicker';

/**
 * Site-wide settings (steps 29-31), combined into one page: general
 * identity, SMTP + test-send, cache clear - all "configuration du site" in
 * MAIN.md's own grouping, all backed by the same SiteConfig singleton
 * (SMTP settings aside, which are stored on it but not shown publicly).
 */
export default function SiteConfig() {
    const [config, setConfig] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saved, setSaved] = useState(false);
    const [pickerField, setPickerField] = useState(null); // 'logoUrl' | 'faviconUrl' | null
    const [testEmail, setTestEmail] = useState('');
    const [testResult, setTestResult] = useState(null);
    const [clearingCache, setClearingCache] = useState(false);
    const [cacheResult, setCacheResult] = useState(null);

    useEffect(() => {
        client
            .get('/admin/site-config')
            .then(({ data }) => setConfig(data))
            .finally(() => setLoading(false));
    }, []);

    const setField = (field, value) => setConfig((prev) => ({ ...prev, [field]: value }));

    const save = async (event) => {
        event.preventDefault();
        setSaved(false);
        const { data } = await client.patch('/admin/site-config', config, {
            headers: { 'Content-Type': 'application/merge-patch+json' },
        });
        setConfig(data);
        setSaved(true);
    };

    const sendTestMail = async () => {
        setTestResult(null);
        const { data } = await client.post('/admin/site-config/test-mail', { to: testEmail });
        setTestResult(data);
    };

    const clearCache = async () => {
        setClearingCache(true);
        setCacheResult(null);
        try {
            const { data } = await client.post('/admin/site-config/clear-cache');
            setCacheResult(data);
        } finally {
            setClearingCache(false);
        }
    };

    if (loading || !config) {
        return <p>Chargement...</p>;
    }

    return (
        <div>
            <h1 className="mb-4">Configuration du site</h1>

            <Form onSubmit={save} style={{ maxWidth: '640px' }}>
                <h2 className="h5">Général</h2>
                <Form.Group className="mb-3" controlId="siteConfigName">
                    <Form.Label>Nom du site</Form.Label>
                    <Form.Control
                        type="text"
                        value={config.siteName}
                        onChange={(e) => setField('siteName', e.target.value)}
                        required
                    />
                </Form.Group>
                <Form.Group className="mb-3">
                    <Form.Label>Logo</Form.Label>
                    <div className="d-flex align-items-center gap-3">
                        {config.logoUrl && <Image src={config.logoUrl} alt="Logo" height={48} />}
                        <Button size="sm" variant="outline-secondary" onClick={() => setPickerField('logoUrl')}>
                            {config.logoUrl ? 'Changer le logo' : 'Choisir un logo'}
                        </Button>
                    </div>
                </Form.Group>
                <Form.Group className="mb-4">
                    <Form.Label>Favicon</Form.Label>
                    <div className="d-flex align-items-center gap-3">
                        {config.faviconUrl && <Image src={config.faviconUrl} alt="Favicon" height={32} />}
                        <Button size="sm" variant="outline-secondary" onClick={() => setPickerField('faviconUrl')}>
                            {config.faviconUrl ? 'Changer le favicon' : 'Choisir un favicon'}
                        </Button>
                    </div>
                </Form.Group>

                <h2 className="h5">SMTP</h2>
                <Form.Group className="mb-3" controlId="siteConfigSmtpHost">
                    <Form.Label>Hôte SMTP</Form.Label>
                    <Form.Control
                        type="text"
                        placeholder="laisser vide pour utiliser la configuration par défaut du serveur"
                        value={config.smtpHost ?? ''}
                        onChange={(e) => setField('smtpHost', e.target.value || null)}
                    />
                </Form.Group>
                <Form.Group className="mb-3" controlId="siteConfigSmtpPort">
                    <Form.Label>Port</Form.Label>
                    <Form.Control
                        type="number"
                        value={config.smtpPort ?? ''}
                        onChange={(e) => setField('smtpPort', e.target.value ? Number(e.target.value) : null)}
                    />
                </Form.Group>
                <Form.Group className="mb-3" controlId="siteConfigSmtpUser">
                    <Form.Label>Utilisateur</Form.Label>
                    <Form.Control
                        type="text"
                        value={config.smtpUser ?? ''}
                        onChange={(e) => setField('smtpUser', e.target.value || null)}
                    />
                </Form.Group>
                <Form.Group className="mb-3" controlId="siteConfigSmtpPassword">
                    <Form.Label>Mot de passe</Form.Label>
                    <Form.Control
                        type="password"
                        value={config.smtpPassword ?? ''}
                        onChange={(e) => setField('smtpPassword', e.target.value || null)}
                    />
                </Form.Group>
                <Form.Group className="mb-4" controlId="siteConfigSmtpEncryption">
                    <Form.Label>Chiffrement</Form.Label>
                    <Form.Select
                        value={config.smtpEncryption ?? ''}
                        onChange={(e) => setField('smtpEncryption', e.target.value || null)}
                    >
                        <option value="">Non précisé</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="none">Aucun</option>
                    </Form.Select>
                </Form.Group>

                {saved && <div className="alert alert-success">Configuration enregistrée.</div>}

                <Button type="submit" variant="primary">
                    Enregistrer
                </Button>
            </Form>

            <h2 className="h5 mt-4">Tester l'envoi d'email</h2>
            <div className="d-flex align-items-center gap-2" style={{ maxWidth: '480px' }}>
                <Form.Control
                    type="email"
                    placeholder="destinataire@example.com"
                    value={testEmail}
                    onChange={(e) => setTestEmail(e.target.value)}
                />
                <Button variant="outline-primary" onClick={sendTestMail}>
                    Envoyer un test
                </Button>
            </div>
            {testResult && (
                <div className={`alert mt-2 ${testResult.success ? 'alert-success' : 'alert-danger'}`}>
                    {testResult.success ? 'Email de test envoyé.' : `Échec : ${testResult.error}`}
                </div>
            )}

            <h2 className="h5 mt-4">Cache</h2>
            <Button variant="outline-warning" onClick={clearCache} disabled={clearingCache}>
                {clearingCache ? 'Vidage en cours...' : 'Vider le cache'}
            </Button>
            {cacheResult && (
                <div className={`alert mt-2 ${cacheResult.success ? 'alert-success' : 'alert-danger'}`}>
                    {cacheResult.success ? 'Cache vidé.' : `Échec : ${cacheResult.error}`}
                </div>
            )}

            <MediaPicker
                show={Boolean(pickerField)}
                onHide={() => setPickerField(null)}
                types={['img']}
                onSelect={(file) => setField(pickerField, file.url)}
            />
        </div>
    );
}
