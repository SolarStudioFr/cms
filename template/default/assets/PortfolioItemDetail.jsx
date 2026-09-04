import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';

/** Public detail view of one published PortfolioItem (step 18). */
export default function PortfolioItemDetail() {
    const { id } = useParams();
    const [item, setItem] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);

    useEffect(() => {
        setLoading(true);
        setNotFound(false);
        client
            .get(`/portfolio/${id}`)
            .then(({ data }) => setItem(data))
            .catch(() => setNotFound(true))
            .finally(() => setLoading(false));
    }, [id]);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (notFound || !item) {
        return <p>Réalisation introuvable.</p>;
    }

    return (
        <div>
            <p>
                <Link to="/portfolio">&larr; Retour aux réalisations</Link>
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
                boundary as PageList's content rendering. */}
            <div dangerouslySetInnerHTML={{ __html: item.content }} />
        </div>
    );
}
