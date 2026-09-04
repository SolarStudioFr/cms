import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from './api/client';

/** Public list of every published NewsArticle (step 20), same data-fetch shape as PortfolioItemList. */
export default function NewsArticleList() {
    const [articles, setArticles] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/news')
            .then(({ data }) => setArticles(data))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (articles.length === 0) {
        return <p>Aucune actualité publiée pour le moment.</p>;
    }

    return (
        <div className="row g-4">
            {articles.map((article) => (
                <div className="col-sm-6 col-md-4" key={article.id}>
                    <Link to={`/news/${article.id}`} className="text-decoration-none text-body">
                        {article.coverImageUrl && (
                            <img
                                src={article.coverImageUrl}
                                alt={article.coverImageAlt || ''}
                                className="img-fluid mb-2"
                                style={{ aspectRatio: '4 / 3', objectFit: 'cover', width: '100%' }}
                            />
                        )}
                        <h2 className="h5 mb-1">{article.title}</h2>
                        <small className="text-muted">{new Date(article.createdAt).toLocaleDateString()}</small>
                    </Link>
                </div>
            ))}
        </div>
    );
}
