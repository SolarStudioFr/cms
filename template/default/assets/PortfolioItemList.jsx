import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from './api/client';

/** Public grid of every published PortfolioItem (step 18), same data-fetch shape as PageList. */
export default function PortfolioItemList() {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/portfolio')
            .then(({ data }) => setItems(data))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (items.length === 0) {
        return <p>Aucune réalisation publiée pour le moment.</p>;
    }

    return (
        <div className="row g-4">
            {items.map((item) => (
                <div className="col-sm-6 col-md-4" key={item.id}>
                    <Link to={`/portfolio/${item.id}`} className="text-decoration-none text-body">
                        {item.coverImageUrl && (
                            <img
                                src={item.coverImageUrl}
                                alt={item.coverImageAlt || ''}
                                className="img-fluid mb-2"
                                style={{ aspectRatio: '4 / 3', objectFit: 'cover', width: '100%' }}
                            />
                        )}
                        <h2 className="h5 mb-1">{item.title}</h2>
                    </Link>
                </div>
            ))}
        </div>
    );
}
