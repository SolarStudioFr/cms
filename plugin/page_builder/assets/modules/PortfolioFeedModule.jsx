import React from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import useDomainTranslator from '../useDomainTranslator';

const BACKGROUNDS = { white: 'module.feed.backgroundWhite', muted: 'module.feed.backgroundMuted' };

/** Admin editor for one Portfolio feed block (step 44): section header + display options, no live preview of the actual feed (rendered dynamically, see renderToHtml below). */
function PortfolioFeedEdit({ props, onChange }) {
    const { t } = useDomainTranslator('page_builder');

    return (
        <div className="d-flex flex-column gap-2">
            <Form.Control
                size="sm"
                placeholder={t('module.feed.eyebrow')}
                value={props.eyebrow}
                onChange={(event) => onChange({ ...props, eyebrow: event.target.value })}
            />
            <Form.Control
                placeholder={t('module.feed.sectionTitle')}
                value={props.title}
                onChange={(event) => onChange({ ...props, title: event.target.value })}
            />
            <Form.Group className="d-flex align-items-center gap-2">
                <Form.Label className="mb-0 small">{t('module.feed.count')}</Form.Label>
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
            <Form.Control
                size="sm"
                placeholder={t('module.feed.seeAllText')}
                value={props.seeAllText}
                onChange={(event) => onChange({ ...props, seeAllText: event.target.value })}
            />
            <Form.Select
                size="sm"
                value={props.background}
                onChange={(event) => onChange({ ...props, background: event.target.value })}
                style={{ maxWidth: '180px' }}
            >
                {Object.entries(BACKGROUNDS).map(([value, key]) => (
                    <option key={value} value={value}>
                        {t(key)}
                    </option>
                ))}
            </Form.Select>
        </div>
    );
}

/** Registry entry for the builder's Portfolio feed module (step 44). */
export default {
    type: 'portfolio-feed',
    label: 'module.portfolioFeed.label',
    defaultProps: { eyebrow: '', title: '', count: 3, seeAllText: 'Tout voir', background: 'white' },
    Edit: PortfolioFeedEdit,
    /**
     * Unlike every other module, this doesn't render the actual content:
     * the list of "latest N portfolio items" can go stale between page save
     * and page view, so the stored HTML only contains a placeholder marker
     * (`data-builder-feed`) - the public theme fetches and fills it in at
     * display time (template/default/assets/builderFeedHydrator.js). This
     * mechanism is shared verbatim by the News feed module (step 45).
     *
     * @param {{eyebrow: string, title: string, count: number, seeAllText: string, background: 'white'|'muted'}} props
     */
    render: (props) => {
        const eyebrow = props.eyebrow ? `<p class="builder-eyebrow">${htmlEscape(props.eyebrow)}</p>` : '';
        const title = props.title ? `<h2>${htmlEscape(props.title)}</h2>` : '';
        const header = eyebrow || title ? `<div class="builder-section-header">${eyebrow}${title}</div>` : '';
        const seeAll = props.seeAllText
            ? `<a href="/portfolio" class="builder-feed-see-all">${htmlEscape(props.seeAllText)}</a>`
            : '';

        return (
            `<section class="builder-feed builder-feed-bg-${htmlEscape(props.background || 'white')}" data-builder-feed="portfolio" data-count="${Number(props.count) || 3}">` +
            `${header}` +
            `<div class="builder-feed-items" data-builder-feed-items><p class="builder-feed-loading">Chargement...</p></div>` +
            `${seeAll}` +
            `</section>`
        );
    },
};
