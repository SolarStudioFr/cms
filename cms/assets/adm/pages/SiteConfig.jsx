import React, { useEffect, useState } from 'react';
import { Button, Form, Image } from 'react-bootstrap';
import client from '../api/client';
import MediaPicker from '../components/MediaPicker';
import { useTranslator } from '../i18n/TranslationContext';

/**
 * Site-wide settings (steps 29-31), combined into one page: general
 * identity, SMTP + test-send, cache clear - all "configuration du site" in
 * MAIN.md's own grouping, all backed by the same SiteConfig singleton
 * (SMTP settings aside, which are stored on it but not shown publicly).
 */
export default function SiteConfig() {
    const { t } = useTranslator();
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
        return <p>{t('common.loading')}</p>;
    }

    return (
        <div>
            <h1 className="mb-4">{t('siteConfig.title')}</h1>

            <Form onSubmit={save} style={{ maxWidth: '640px' }}>
                <h2 className="h5">{t('siteConfig.general')}</h2>
                <Form.Group className="mb-3" controlId="siteConfigName">
                    <Form.Label>{t('siteConfig.siteName')}</Form.Label>
                    <Form.Control
                        type="text"
                        value={config.siteName}
                        onChange={(e) => setField('siteName', e.target.value)}
                        required
                    />
                </Form.Group>
                <Form.Group className="mb-3">
                    <Form.Label>{t('siteConfig.logo')}</Form.Label>
                    <div className="d-flex align-items-center gap-3">
                        {config.logoUrl && <Image src={config.logoUrl} alt="Logo" height={48} />}
                        <Button size="sm" variant="outline-secondary" onClick={() => setPickerField('logoUrl')}>
                            {config.logoUrl ? t('siteConfig.changeLogo') : t('siteConfig.chooseLogo')}
                        </Button>
                    </div>
                </Form.Group>
                <Form.Group className="mb-4">
                    <Form.Label>{t('siteConfig.favicon')}</Form.Label>
                    <div className="d-flex align-items-center gap-3">
                        {config.faviconUrl && <Image src={config.faviconUrl} alt="Favicon" height={32} />}
                        <Button size="sm" variant="outline-secondary" onClick={() => setPickerField('faviconUrl')}>
                            {config.faviconUrl ? t('siteConfig.changeFavicon') : t('siteConfig.chooseFavicon')}
                        </Button>
                    </div>
                </Form.Group>

                <h2 className="h5">{t('siteConfig.smtp')}</h2>
                <Form.Group className="mb-3" controlId="siteConfigSmtpHost">
                    <Form.Label>{t('siteConfig.smtpHost')}</Form.Label>
                    <Form.Control
                        type="text"
                        placeholder={t('siteConfig.smtpHostPlaceholder')}
                        value={config.smtpHost ?? ''}
                        onChange={(e) => setField('smtpHost', e.target.value || null)}
                    />
                </Form.Group>
                <Form.Group className="mb-3" controlId="siteConfigSmtpPort">
                    <Form.Label>{t('siteConfig.smtpPort')}</Form.Label>
                    <Form.Control
                        type="number"
                        value={config.smtpPort ?? ''}
                        onChange={(e) => setField('smtpPort', e.target.value ? Number(e.target.value) : null)}
                    />
                </Form.Group>
                <Form.Group className="mb-3" controlId="siteConfigSmtpUser">
                    <Form.Label>{t('siteConfig.smtpUser')}</Form.Label>
                    <Form.Control
                        type="text"
                        value={config.smtpUser ?? ''}
                        onChange={(e) => setField('smtpUser', e.target.value || null)}
                    />
                </Form.Group>
                <Form.Group className="mb-3" controlId="siteConfigSmtpPassword">
                    <Form.Label>{t('siteConfig.smtpPassword')}</Form.Label>
                    <Form.Control
                        type="password"
                        value={config.smtpPassword ?? ''}
                        onChange={(e) => setField('smtpPassword', e.target.value || null)}
                    />
                </Form.Group>
                <Form.Group className="mb-4" controlId="siteConfigSmtpEncryption">
                    <Form.Label>{t('siteConfig.smtpEncryption')}</Form.Label>
                    <Form.Select
                        value={config.smtpEncryption ?? ''}
                        onChange={(e) => setField('smtpEncryption', e.target.value || null)}
                    >
                        <option value="">{t('siteConfig.encryptionUnspecified')}</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="none">{t('siteConfig.encryptionNone')}</option>
                    </Form.Select>
                </Form.Group>

                {saved && <div className="alert alert-success">{t('siteConfig.saved')}</div>}

                <Button type="submit" variant="primary">
                    {t('common.save')}
                </Button>
            </Form>

            <h2 className="h5 mt-4">{t('siteConfig.testMail')}</h2>
            <div className="d-flex align-items-center gap-2" style={{ maxWidth: '480px' }}>
                <Form.Control
                    type="email"
                    placeholder={t('siteConfig.testMailPlaceholder')}
                    value={testEmail}
                    onChange={(e) => setTestEmail(e.target.value)}
                />
                <Button variant="outline-primary" onClick={sendTestMail}>
                    {t('siteConfig.sendTest')}
                </Button>
            </div>
            {testResult && (
                <div className={`alert mt-2 ${testResult.success ? 'alert-success' : 'alert-danger'}`}>
                    {testResult.success ? t('siteConfig.testMailSuccess') : t('siteConfig.testMailFailure', { error: testResult.error })}
                </div>
            )}

            <h2 className="h5 mt-4">{t('siteConfig.cache')}</h2>
            <Button variant="outline-warning" onClick={clearCache} disabled={clearingCache}>
                {clearingCache ? t('siteConfig.clearingCache') : t('siteConfig.clearCache')}
            </Button>
            {cacheResult && (
                <div className={`alert mt-2 ${cacheResult.success ? 'alert-success' : 'alert-danger'}`}>
                    {cacheResult.success ? t('siteConfig.cacheCleared') : t('siteConfig.cacheFailure', { error: cacheResult.error })}
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
