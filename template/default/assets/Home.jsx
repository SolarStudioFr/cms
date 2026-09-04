import React, { useEffect, useState } from 'react';
import client from './api/client';

/**
 * Public homepage (step 21): renders the content configured via the
 * Homepage admin plugin (fallback editor or builder HTML - same contract as
 * PageList's `content`). `GET /api/homepage` always returns something (the
 * backend auto-creates an empty row on first read), so an unconfigured
 * homepage just renders as empty rather than erroring.
 */
export default function Home() {
    const [content, setContent] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/homepage')
            .then(({ data }) => setContent(data.content))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <p>Chargement...</p>;
    }

    if (!content) {
        return <p>Bienvenue sur Solar CMS.</p>;
    }

    // Admin-authored HTML (fallback editor or builder) - same trust boundary
    // as PageList's content rendering.
    return <div dangerouslySetInnerHTML={{ __html: content }} />;
}
