import React, { useEffect, useRef } from 'react';
import hydrateBuilderFeeds from './builderFeedHydrator';

/**
 * Renders admin-authored HTML (fallback editor or builder output - Page,
 * PortfolioItem, NewsArticle, Homepage all share this same `content`
 * contract) and, once mounted, hydrates any builder feed placeholder found
 * inside it (steps 44/45) - the one spot every one of those content types
 * routes through, so the dynamic-feed mechanism only needs to be wired up
 * here rather than duplicated in each detail view.
 *
 * @param {string} html
 * @param {string} [className]
 */
export default function BuilderContent({ html, className }) {
    const containerRef = useRef(null);

    useEffect(() => {
        if (containerRef.current) {
            hydrateBuilderFeeds(containerRef.current);
        }
    }, [html]);

    if (!html) {
        return null;
    }

    return <div ref={containerRef} className={className} dangerouslySetInnerHTML={{ __html: html }} />;
}
