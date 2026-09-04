import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from './api/client';

/** Public list of every published Actualite (step 20), same data-fetch shape as RealisationList. */
export default function ActualiteList() {
    const [actualites, setActualites] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/actualites')
            .then(({ data }) => setActualites(data))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (actualites.length === 0) {
        return <p>Aucune actualité publiée pour le moment.</p>;
    }

    return (
        <div className="row g-4">
            {actualites.map((actualite) => (
                <div className="col-sm-6 col-md-4" key={actualite.id}>
                    <Link to={`/actualites/${actualite.id}`} className="text-decoration-none text-body">
                        {actualite.coverImageUrl && (
                            <img
                                src={actualite.coverImageUrl}
                                alt={actualite.coverImageAlt || ''}
                                className="img-fluid mb-2"
                                style={{ aspectRatio: '4 / 3', objectFit: 'cover', width: '100%' }}
                            />
                        )}
                        <h2 className="h5 mb-1">{actualite.title}</h2>
                        <small className="text-muted">{new Date(actualite.createdAt).toLocaleDateString()}</small>
                    </Link>
                </div>
            ))}
        </div>
    );
}
