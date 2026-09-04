import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';

/** Public detail view of one published NewsArticle (step 20). */
export default function NewsArticleDetail() {
    const { id } = useParams();
    const [article, setArticle] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);

    useEffect(() => {
        setLoading(true);
        setNotFound(false);
        client
            .get(`/news/${id}`)
            .then(({ data }) => setArticle(data))
            .catch(() => setNotFound(true))
            .finally(() => setLoading(false));
    }, [id]);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (notFound || !article) {
        return <p>Actualité introuvable.</p>;
    }

    return (
        <div>
            <p>
                <Link to="/news">&larr; Retour aux actualités</Link>
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
                boundary as PageList's content rendering. */}
            <div dangerouslySetInnerHTML={{ __html: article.content }} />
        </div>
    );
}
