import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Button, Form, Nav } from 'react-bootstrap';
import { useNavigate, useParams } from 'react-router-dom';
import client from './api/client';
import useDomainTranslator from './useDomainTranslator';
import useContentLocale from './useContentLocale';

// Shared with the rest of the admin via Module Federation (step 09) - no
// page-builder integration for campaign content, see Campaign entity's docblock.
const RichTextEditor = lazy(() => import('adm_host/RichTextEditor'));

export default function CampaignForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { t } = useDomainTranslator('newsletter');
    const isEditing = Boolean(id);

    const [subject, setSubject] = useState('');
    const [content, setContent] = useState('');
    const [loading, setLoading] = useState(isEditing);
    const [error, setError] = useState(null);

    // Content-language switcher (step 58 follow-up).
    const { activeLangs, editingLocale, setEditingLocale, isDefaultLocale, fieldValue, setField, saveAllTranslations } =
        useContentLocale('newsletter_campaign', isEditing ? Number(id) : null);

    useEffect(() => {
        if (!isEditing) {
            return;
        }
        client
            .get(`/admin/newsletter/campaigns/${id}`)
            .then(({ data }) => {
                setSubject(data.subject);
                setContent(data.content);
            })
            .catch(() => setError(t('newsletter.loadError')))
            .finally(() => setLoading(false));
    }, [id, isEditing, t]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);

        try {
            const savedId = isEditing
                ? Number(id)
                : (await client.post('/admin/newsletter/campaigns', { subject, content })).data.id;
            if (isEditing) {
                await client.patch(
                    `/admin/newsletter/campaigns/${id}`,
                    { subject, content },
                    { headers: { 'Content-Type': 'application/merge-patch+json' } },
                );
            }
            await saveAllTranslations(savedId, { subject, content });
            navigate('/newsletter');
        } catch {
            setError(t('newsletter.saveError'));
        }
    };

    if (loading) {
        return <p>{t('newsletter.loading')}</p>;
    }

    return (
        <div>
            <h1>{isEditing ? t('newsletter.editCampaign') : t('newsletter.newCampaign')}</h1>

            {activeLangs.length > 1 && (
                <Nav variant="pills" className="mb-3">
                    {activeLangs.map((lang) => (
                        <Nav.Item key={lang.code}>
                            <Nav.Link active={lang.code === editingLocale} onClick={() => setEditingLocale(lang.code)}>
                                {lang.label}
                            </Nav.Link>
                        </Nav.Item>
                    ))}
                </Nav>
            )}

            {error && <div className="alert alert-danger">{error}</div>}

            <Form onSubmit={handleSubmit} style={{ maxWidth: '640px' }}>
                <Form.Group className="mb-3" controlId="campaignSubject">
                    <Form.Label>{t('newsletter.subject')}</Form.Label>
                    <Form.Control
                        type="text"
                        value={fieldValue(subject, 'subject')}
                        onChange={(e) => setField('subject', setSubject)(e.target.value)}
                        required={isDefaultLocale}
                    />
                </Form.Group>

                <Form.Group className="mb-3" controlId="campaignContent">
                    <Form.Label>{t('newsletter.content')}</Form.Label>
                    {/* key={editingLocale}: forces a remount on language switch, see PageForm.jsx's comment. */}
                    <Suspense fallback={<p>{t('newsletter.loadingEditor')}</p>}>
                        <RichTextEditor
                            key={editingLocale}
                            value={fieldValue(content, 'content')}
                            onChange={setField('content', setContent)}
                            placeholder={t('newsletter.contentPlaceholder')}
                        />
                    </Suspense>
                </Form.Group>

                <Button type="submit" variant="primary">
                    {t('common.save')}
                </Button>
                <Button type="button" variant="link" onClick={() => navigate('/newsletter')}>
                    {t('common.cancel')}
                </Button>
            </Form>
        </div>
    );
}
