import React, { useEffect, useState } from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import client from './api/client';

const ORDERS = { desc: 'Plus récent en premier', asc: 'Plus ancien en premier' };

/**
 * Admin editor for one Portfolio list block (step 51): display options
 * only, no live preview of the actual list (rendered dynamically at
 * display time, same mechanism as the Portfolio feed module - see
 * builderFeedHydrator.js). The tag checkboxes are populated from the
 * Portfolio plugin's own admin endpoint (step 38) - a plain cross-plugin
 * HTTP call, not an import.
 */
function PortfolioListEdit({ props, onChange }) {
    const [tags, setTags] = useState([]);
    const selectedTagIds = props.tagIds ?? [];

    useEffect(() => {
        client
            .get('/admin/portfolio/tags')
            .then(({ data }) => setTags(data))
            .catch(() => setTags([]));
    }, []);

    const toggleTag = (id) => {
        const next = selectedTagIds.includes(id) ? selectedTagIds.filter((tagId) => tagId !== id) : [...selectedTagIds, id];
        onChange({ ...props, tagIds: next });
    };

    return (
        <div className="d-flex flex-column gap-2">
            <Form.Group className="d-flex align-items-center gap-2">
                <Form.Label className="mb-0 small">Nombre de réalisations</Form.Label>
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
            {0 === tags.length ? (
                <p className="text-muted small mb-0">Aucun tag disponible.</p>
            ) : (
                <div className="d-flex flex-wrap gap-3">
                    {tags.map((tag) => (
                        <Form.Check
                            key={tag.id}
                            type="checkbox"
                            id={`portfolio-list-tag-${tag.id}`}
                            label={tag.name}
                            checked={selectedTagIds.includes(tag.id)}
                            onChange={() => toggleTag(tag.id)}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

/** Registry entry for the builder's Portfolio list module (step 51). */
export default {
    type: 'portfolio-list',
    label: 'Réalisations (liste)',
    defaultProps: { count: 6, order: 'desc', tagIds: [] },
    Edit: PortfolioListEdit,
    /**
     * Same placeholder-marker mechanism as PortfolioFeedModule (step 44,
     * reused here): `data-builder-feed="portfolio"` matches the feed
     * module's own marker so builderFeedHydrator.js's fetch/render logic for
     * "portfolio" is shared verbatim - `data-order` and `data-tags` (a
     * comma-separated id list) are this step's additions.
     *
     * @param {{count: number, order: 'desc'|'asc', tagIds: number[]}} props
     */
    render: (props) => {
        const count = Number(props.count) || 6;
        const order = 'asc' === props.order ? 'asc' : 'desc';
        const tagIds = (props.tagIds ?? []).filter(Boolean);
        const tagsAttr = tagIds.length ? ` data-tags="${htmlEscape(tagIds.join(','))}"` : '';

        return (
            `<div class="builder-list" data-builder-feed="portfolio" data-count="${count}" data-order="${order}"${tagsAttr}>` +
            `<div class="builder-feed-items" data-builder-feed-items><p class="builder-feed-loading">Chargement...</p></div>` +
            `</div>`
        );
    },
};
