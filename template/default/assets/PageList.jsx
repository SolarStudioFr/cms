import React, { useEffect, useState } from 'react';
import client from './api/client';
import BuilderContent from './BuilderContent';
import { useTranslator } from './i18n/TranslationContext';

export default function PageList() {
    const { t } = useTranslator();
    const [pages, setPages] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/pages')
            .then(({ data }) => setPages(data))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <p>{t('common.loading')}</p>;
    }

    if (pages.length === 0) {
        return <p>{t('pages.empty')}</p>;
    }

    return (
        <ul className="list-unstyled">
            {pages.map((page) => (
                <li key={page.id} className="mb-3">
                    <h2 className="h5 mb-1">{page.title}</h2>
                    <small className="text-muted">
                        {new Date(page.createdAt).toLocaleDateString()}
                    </small>
                    {/* page.content is HTML authored by an admin (fallback editor
                        or builder, both step 09/10+), not visitor input - same
                        trust boundary as any CMS rendering its own admin-authored
                        content. BuilderContent also hydrates any dynamic feed
                        placeholder the builder may have produced (steps 44/45). */}
                    <BuilderContent className="mb-0" html={page.content} />
                </li>
            ))}
        </ul>
    );
}
