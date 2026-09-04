import { useEffect, useState } from 'react';
import client from './api/client';

/**
 * Fetches the public subset of SiteConfig (steps 29-31: name/logo/favicon)
 * once, and applies document.title + the favicon link tag as a side effect
 * - base.html.twig ships a static fallback title, this is what makes it
 * reflect the admin-configured site name/favicon without a server round-trip
 * per page (the public app is an SPA, this runs once on mount).
 */
export default function useSiteConfig() {
    const [siteConfig, setSiteConfig] = useState(null);

    useEffect(() => {
        client.get('/site-config').then(({ data }) => setSiteConfig(data));
    }, []);

    useEffect(() => {
        if (!siteConfig) {
            return;
        }

        document.title = siteConfig.siteName;

        if (siteConfig.faviconUrl) {
            let link = document.querySelector("link[rel~='icon']");
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.head.appendChild(link);
            }
            link.href = siteConfig.faviconUrl;
        }
    }, [siteConfig]);

    return siteConfig;
}
