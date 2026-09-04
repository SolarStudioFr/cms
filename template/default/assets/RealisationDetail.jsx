import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import client from './api/client';

/** Public detail view of one published Realisation (step 18). */
export default function RealisationDetail() {
    const { id } = useParams();
    const [realisation, setRealisation] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);

    useEffect(() => {
        setLoading(true);
        setNotFound(false);
        client
            .get(`/realisations/${id}`)
            .then(({ data }) => setRealisation(data))
            .catch(() => setNotFound(true))
            .finally(() => setLoading(false));
    }, [id]);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (notFound || !realisation) {
        return <p>Réalisation introuvable.</p>;
    }

    return (
        <div>
            <p>
                <Link to="/realisations">&larr; Retour aux réalisations</Link>
            </p>
            <h1>{realisation.title}</h1>
            {realisation.coverImageUrl && (
                <img
                    src={realisation.coverImageUrl}
                    alt={realisation.coverImageAlt || ''}
                    className="img-fluid mb-3"
                />
            )}
            {/* Admin-authored HTML (fallback editor or builder) - same trust
                boundary as PageList's content rendering. */}
            <div dangerouslySetInnerHTML={{ __html: realisation.content }} />
        </div>
    );
}
