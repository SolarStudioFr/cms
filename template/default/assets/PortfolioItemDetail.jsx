import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';
import BuilderContent from './BuilderContent';
import { useTranslator } from './i18n/TranslationContext';

/** Public detail view of one published PortfolioItem (step 18). */
export default function PortfolioItemDetail() {
    const { id } = useParams();
    const { t, locale } = useTranslator();
    const [item, setItem] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);

    useEffect(() => {
        setLoading(true);
        setNotFound(false);
        client
            .get(`/portfolio/${id}`, { params: { locale } })
            .then(({ data }) => setItem(data))
            .catch(() => setNotFound(true))
            .finally(() => setLoading(false));
    }, [id, locale]);

    if (loading) {
        return <p>{t('common.loading')}</p>;
    }

    if (notFound || !item) {
        return <p>{t('portfolio.notFound')}</p>;
    }

    return (
        <div>
            <p>
                <Link to="/portfolio">&larr; {t('portfolio.back')}</Link>
            </p>
            <h1>{item.title}</h1>
            {item.coverImageUrl && (
                <img
                    src={item.coverImageUrl}
                    alt={item.coverImageAlt || ''}
                    className="img-fluid mb-3"
                />
            )}
            {/* Admin-authored HTML (fallback editor or builder) - same trust
                boundary as PageList's content rendering. BuilderContent also
                hydrates any dynamic feed placeholder (steps 44/45). */}
            <BuilderContent html={item.content} />
        </div>
    );
}
