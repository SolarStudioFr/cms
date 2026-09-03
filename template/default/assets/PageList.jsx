import React, { useEffect, useState } from 'react';
import client from './api/client';

export default function PageList() {
    const [pages, setPages] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/pages')
            .then(({ data }) => setPages(data))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (pages.length === 0) {
        return <p>Aucune page publiée pour le moment.</p>;
    }

    return (
        <ul className="list-unstyled">
            {pages.map((page) => (
                <li key={page.id} className="mb-3">
                    <h2 className="h5 mb-1">{page.title}</h2>
                    <small className="text-muted">
                        {new Date(page.createdAt).toLocaleDateString()}
                    </small>
                    <p className="mb-0">{page.content}</p>
                </li>
            ))}
        </ul>
    );
}
