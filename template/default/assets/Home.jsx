import React, { useEffect, useState } from 'react';
import client from './api/client';
import BuilderContent from './BuilderContent';
import { useTranslator } from './i18n/TranslationContext';

/**
 * Public homepage (step 21): renders the content configured via the
 * Homepage admin plugin (fallback editor or builder HTML - same contract as
 * PageList's `content`). `GET /api/homepage` always returns something (the
 * backend auto-creates an empty row on first read), so an unconfigured
 * homepage just renders as empty rather than erroring.
 */
export default function Home() {
    const { t } = useTranslator();
    const [content, setContent] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/homepage')
            .then(({ data }) => setContent(data.content))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <p>{t('common.loading')}</p>;
    }

    if (!content) {
        return <p>{t('home.welcome')}</p>;
    }

    // Admin-authored HTML (fallback editor or builder) - same trust boundary
    // as PageList's content rendering. BuilderContent also hydrates any
    // dynamic feed placeholder the builder may have produced (steps 44/45).
    return <BuilderContent html={content} />;
}
