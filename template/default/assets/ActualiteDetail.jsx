import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';

/** Public detail view of one published Actualite (step 20). */
export default function ActualiteDetail() {
    const { id } = useParams();
    const [actualite, setActualite] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);

    useEffect(() => {
        setLoading(true);
        setNotFound(false);
        client
            .get(`/actualites/${id}`)
            .then(({ data }) => setActualite(data))
            .catch(() => setNotFound(true))
            .finally(() => setLoading(false));
    }, [id]);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (notFound || !actualite) {
        return <p>Actualité introuvable.</p>;
    }

    return (
        <div>
            <p>
                <Link to="/actualites">&larr; Retour aux actualités</Link>
            </p>
            <h1>{actualite.title}</h1>
            <p className="text-muted">{new Date(actualite.createdAt).toLocaleDateString()}</p>
            {actualite.coverImageUrl && (
                <img
                    src={actualite.coverImageUrl}
                    alt={actualite.coverImageAlt || ''}
                    className="img-fluid mb-3"
                />
            )}
            {/* Admin-authored HTML (fallback editor or builder) - same trust
                boundary as PageList's content rendering. */}
            <div dangerouslySetInnerHTML={{ __html: actualite.content }} />
        </div>
    );
}
