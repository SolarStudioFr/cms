import client from './api/client';

// One entry per builder feed module (steps 44/45): the public collection
// endpoint to fetch from, and the detail route each item links to. Both
// endpoints already return every published item ordered newest-first with
// pagination disabled (see PublishedPortfolioItemCollectionProvider /
// PublishedNewsArticleCollectionProvider) - "latest N" is just a client-side
// slice, no dedicated backend endpoint needed for this.
const FEEDS = {
    portfolio: { endpoint: '/portfolio', detailPath: (item) => `/portfolio/${item.id}`, showDate: false },
    news: { endpoint: '/news', detailPath: (item) => `/news/${item.id}`, showDate: true },
};

const ENTITIES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

/** Escapes a value for safe interpolation into the feed items' HTML. */
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ENTITIES[char]);
}

/** Builds the markup for one feed item card, mirroring PortfolioItemList/NewsArticleList's own card layout. */
function itemHtml(feed, item) {
    const image = item.coverImageUrl
        ? `<img src="${escapeHtml(item.coverImageUrl)}" alt="${escapeHtml(item.coverImageAlt || '')}" class="builder-feed-item-image" />`
        : '';
    const date = feed.showDate ? `<small class="builder-feed-item-date">${escapeHtml(new Date(item.createdAt).toLocaleDateString())}</small>` : '';

    return (
        `<a href="${escapeHtml(feed.detailPath(item))}" class="builder-feed-item">` +
        `${image}<h3>${escapeHtml(item.title)}</h3>${date}` +
        `</a>`
    );
}

/**
 * Hydrates every builder feed placeholder found under `root` (steps 44/45,
 * extended by 50/51): the builder produces static HTML once, at save time
 * (see page_builder/renderToHtml.js), but a "latest items" block must
 * always reflect what's published *now* - so its render() only emits a
 * marker (`[data-builder-feed]`) and this function, called after that HTML
 * is injected into the DOM, fetches the live data and fills it in.
 *
 * Generic on purpose: this file has no dependency on the builder plugin
 * (Module Federation or otherwise) - it just knows how to interpret a
 * documented placeholder contract, so the public theme keeps working even
 * with the builder plugin disabled/uninstalled (its stored HTML simply
 * wouldn't contain any `[data-builder-feed]` node to hydrate).
 *
 * Placeholder nodes are re-located from `root` by index right before each
 * write rather than captured once upfront: `root` (BuilderContent's own
 * container, passed in by its caller) is a real React-owned DOM node that
 * never gets replaced, but its dangerouslySetInnerHTML children can be -
 * e.g. a sibling hook in the surrounding app (useSiteConfig/useMenus/
 * usePlugins) resolving shortly after mount triggers a parent re-render,
 * which recreates every child of `root` fresh. A reference to a
 * `[data-builder-feed-items]` node captured before an `await` can end up
 * pointing at one of those now-detached originals.
 *
 * @param {HTMLElement} root container to search within (and including itself)
 */
export default function hydrateBuilderFeeds(root) {
    const placeholderCount = root.querySelectorAll('[data-builder-feed]').length;

    const tasks = [];
    for (let index = 0; index < placeholderCount; index += 1) {
        tasks.push(hydrateOne(root, index));
    }

    return Promise.all(tasks);
}

/** Fetches and fills in the `index`-th `[data-builder-feed]` placeholder currently under `root`. */
async function hydrateOne(root, index) {
    const node = root.querySelectorAll('[data-builder-feed]')[index];
    const feed = node && FEEDS[node.dataset.builderFeed];
    if (!feed) {
        return;
    }

    const count = Math.max(0, parseInt(node.dataset.count, 10) || 0);
    // Steps 50/51 (list modules) additions - both absent/default for a
    // plain feed placeholder (steps 44/45), which keeps their behavior
    // (newest-first, unfiltered) unchanged.
    const order = 'asc' === node.dataset.order ? 'asc' : 'desc';
    const categoryId = node.dataset.category || '';
    const tagIds = node.dataset.tags ? node.dataset.tags.split(',').filter(Boolean) : [];

    let html;
    try {
        const { data } = await client.get(feed.endpoint);
        let items = data ?? [];
        if (categoryId) {
            items = items.filter((item) => String(item.category?.id) === categoryId);
        }
        if (tagIds.length) {
            items = items.filter((item) => (item.tags ?? []).some((tag) => tagIds.includes(String(tag.id))));
        }
        // The API already returns newest-first (see PublishedPortfolioItemCollectionProvider/
        // PublishedNewsArticleCollectionProvider) - "oldest first" is just a client-side reversal.
        if ('asc' === order) {
            items = [...items].reverse();
        }
        items = items.slice(0, count);
        html = items.length
            ? items.map((item) => itemHtml(feed, item)).join('')
            : '<p class="builder-feed-empty">Rien à afficher pour le moment.</p>';
    } catch {
        html = '';
    }

    // Re-locate rather than reuse the `node`/`itemsContainer` captured
    // above the `await`: see this module's docblock.
    const currentNode = root.querySelectorAll('[data-builder-feed]')[index];
    const itemsContainer = currentNode?.querySelector('[data-builder-feed-items]');
    if (itemsContainer) {
        itemsContainer.innerHTML = html;
    }
}
