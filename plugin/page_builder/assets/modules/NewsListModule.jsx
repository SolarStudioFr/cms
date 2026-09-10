import React, { useEffect, useState } from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import client from './api/client';

const ORDERS = { desc: 'Plus récent en premier', asc: 'Plus ancien en premier' };

/**
 * Admin editor for one News list block (step 50): display options only, no
 * live preview of the actual list (rendered dynamically at display time,
 * same mechanism as the News feed module - see builderFeedHydrator.js).
 * The category dropdown is populated from the News plugin's own admin
 * endpoint (step 39) - a plain cross-plugin HTTP call, not an import, so
 * this module still works (with an empty/unfiltered dropdown) if the News
 * plugin were ever removed.
 */
function NewsListEdit({ props, onChange }) {
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        client
            .get('/admin/news/categories')
            .then(({ data }) => setCategories(data))
            .catch(() => setCategories([]));
    }, []);

    return (
        <div className="d-flex flex-column gap-2">
            <Form.Group className="d-flex align-items-center gap-2">
                <Form.Label className="mb-0 small">Nombre d'articles</Form.Label>
                <Form.Control
                    type="number"
                    size="sm"
                    min={1}
                    max={24}
                    style={{ maxWidth: '90px' }}
                    value={props.count}
                    onChange={(event) => onChange({ ...props, count: Number(event.target.value) || 1 })}
                />
            </Form.Group>
            <Form.Select
                size="sm"
                style={{ maxWidth: '220px' }}
                value={props.order}
                onChange={(event) => onChange({ ...props, order: event.target.value })}
            >
                {Object.entries(ORDERS).map(([value, label]) => (
                    <option key={value} value={value}>
                        {label}
                    </option>
                ))}
            </Form.Select>
            <Form.Select
                size="sm"
                style={{ maxWidth: '220px' }}
                value={props.categoryId}
                onChange={(event) => onChange({ ...props, categoryId: event.target.value })}
            >
                <option value="">Toutes les catégories</option>
                {categories.map((category) => (
                    <option key={category.id} value={category.id}>
                        {category.name}
                    </option>
                ))}
            </Form.Select>
        </div>
    );
}

/** Registry entry for the builder's News list module (step 50). */
export default {
    type: 'news-list',
    label: 'Actualités (liste)',
    defaultProps: { count: 6, order: 'desc', categoryId: '' },
    Edit: NewsListEdit,
    /**
     * Same placeholder-marker mechanism as NewsFeedModule (step 45, reused
     * here): `data-builder-feed="news"` is intentionally the exact same
     * marker the feed module uses, so builderFeedHydrator.js's existing
     * fetch/render logic for "news" is shared verbatim - `data-order` and
     * `data-category` are the two additions this step needs (both optional,
     * ignored/absent for the feed module's own placeholders so its
     * behavior is unchanged).
     *
     * @param {{count: number, order: 'desc'|'asc', categoryId: string|number}} props
     */
    render: (props) => {
        const count = Number(props.count) || 6;
        const order = 'asc' === props.order ? 'asc' : 'desc';
        const categoryAttr = props.categoryId ? ` data-category="${htmlEscape(String(props.categoryId))}"` : '';

        return (
            `<div class="builder-list" data-builder-feed="news" data-count="${count}" data-order="${order}"${categoryAttr}>` +
            `<div class="builder-feed-items" data-builder-feed-items><p class="builder-feed-loading">Chargement...</p></div>` +
            `</div>`
        );
    },
};
