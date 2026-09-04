import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from './api/client';

/** Public grid of every published Realisation (step 18), same data-fetch shape as PageList. */
export default function RealisationList() {
    const [realisations, setRealisations] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/realisations')
            .then(({ data }) => setRealisations(data))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (realisations.length === 0) {
        return <p>Aucune réalisation publiée pour le moment.</p>;
    }

    return (
        <div className="row g-4">
            {realisations.map((realisation) => (
                <div className="col-sm-6 col-md-4" key={realisation.id}>
                    <Link to={`/realisations/${realisation.id}`} className="text-decoration-none text-body">
                        {realisation.coverImageUrl && (
                            <img
                                src={realisation.coverImageUrl}
                                alt={realisation.coverImageAlt || ''}
                                className="img-fluid mb-2"
                                style={{ aspectRatio: '4 / 3', objectFit: 'cover', width: '100%' }}
                            />
                        )}
                        <h2 className="h5 mb-1">{realisation.title}</h2>
                    </Link>
                </div>
            ))}
        </div>
    );
}
