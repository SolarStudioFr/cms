import React from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import useDomainTranslator from '../useDomainTranslator';

const BACKGROUNDS = { white: 'module.feed.backgroundWhite', muted: 'module.feed.backgroundMuted' };

/** Admin editor for one News feed block (step 45) - same fields/layout as PortfolioFeedModule (step 44). */
function NewsFeedEdit({ props, onChange }) {
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

/** Registry entry for the builder's News feed module (step 45). */
export default {
    type: 'news-feed',
    label: 'module.newsFeed.label',
    defaultProps: { eyebrow: '', title: '', count: 3, seeAllText: 'Tout voir', background: 'white' },
    Edit: NewsFeedEdit,
    /**
     * Same dynamic-rendering mechanism as PortfolioFeedModule (step 44,
     * designed there and reused verbatim here): the stored HTML is only a
     * placeholder marker, hydrated at display time by the public theme
     * (template/default/assets/builderFeedHydrator.js).
     *
     * @param {{eyebrow: string, title: string, count: number, seeAllText: string, background: 'white'|'muted'}} props
     */
    render: (props) => {
        const eyebrow = props.eyebrow ? `<p class="builder-eyebrow">${htmlEscape(props.eyebrow)}</p>` : '';
        const title = props.title ? `<h2>${htmlEscape(props.title)}</h2>` : '';
        const header = eyebrow || title ? `<div class="builder-section-header">${eyebrow}${title}</div>` : '';
        const seeAll = props.seeAllText
            ? `<a href="/news" class="builder-feed-see-all">${htmlEscape(props.seeAllText)}</a>`
            : '';

        return (
            `<section class="builder-feed builder-feed-bg-${htmlEscape(props.background || 'white')}" data-builder-feed="news" data-count="${Number(props.count) || 3}">` +
            `${header}` +
            `<div class="builder-feed-items" data-builder-feed-items><p class="builder-feed-loading">Chargement...</p></div>` +
            `${seeAll}` +
            `</section>`
        );
    },
};
