import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';
import BuilderContent from './BuilderContent';
import { useTranslator } from './i18n/TranslationContext';

/** Public detail view of one published NewsArticle (step 20). */
export default function NewsArticleDetail() {
    const { id } = useParams();
    const { t, locale } = useTranslator();
    const [article, setArticle] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);

    useEffect(() => {
        setLoading(true);
        setNotFound(false);
        client
            .get(`/news/${id}`, { params: { locale } })
            .then(({ data }) => setArticle(data))
            .catch(() => setNotFound(true))
            .finally(() => setLoading(false));
    }, [id, locale]);

    if (loading) {
        return <p>{t('common.loading')}</p>;
    }

    if (notFound || !article) {
        return <p>{t('news.notFound')}</p>;
    }

    return (
        <div>
            <p>
                <Link to="/news">&larr; {t('news.back')}</Link>
            </p>
            <h1>{article.title}</h1>
            <p className="text-muted">{new Date(article.createdAt).toLocaleDateString()}</p>
            {article.coverImageUrl && (
                <img
                    src={article.coverImageUrl}
                    alt={article.coverImageAlt || ''}
                    className="img-fluid mb-3"
                />
            )}
            {/* Admin-authored HTML (fallback editor or builder) - same trust
                boundary as PageList's content rendering. BuilderContent also
                hydrates any dynamic feed placeholder (steps 44/45). */}
            <BuilderContent html={article.content} />
        </div>
    );
}
