import React from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import useDomainTranslator from '../useDomainTranslator';

/** Admin editor for one Hero block (step 40): eyebrow, h1 title, lead text, two optional CTA buttons. */
function HeroEdit({ props, onChange }) {
    const { t } = useDomainTranslator('page_builder');
    const set = (field) => (event) => onChange({ ...props, [field]: event.target.value });

    return (
        <div className="d-flex flex-column gap-2">
            <Form.Control size="sm" placeholder={t('module.hero.eyebrow')} value={props.eyebrow} onChange={set('eyebrow')} />
            <Form.Control placeholder={t('module.hero.title')} value={props.title} onChange={set('title')} />
            <Form.Control as="textarea" rows={2} placeholder={t('module.hero.lead')} value={props.lead} onChange={set('lead')} />
            <div className="d-flex gap-2">
                <Form.Control size="sm" placeholder={t('module.hero.primaryText')} value={props.primaryText} onChange={set('primaryText')} />
                <Form.Control size="sm" placeholder={t('module.hero.primaryUrl')} value={props.primaryUrl} onChange={set('primaryUrl')} />
            </div>
            <div className="d-flex gap-2">
                <Form.Control size="sm" placeholder={t('module.hero.secondaryText')} value={props.secondaryText} onChange={set('secondaryText')} />
                <Form.Control size="sm" placeholder={t('module.hero.secondaryUrl')} value={props.secondaryUrl} onChange={set('secondaryUrl')} />
            </div>
        </div>
    );
}

/** Registry entry for the builder's Hero module (step 40). */
export default {
    type: 'hero',
    label: 'module.hero.label',
    defaultProps: {
        eyebrow: '',
        title: '',
        lead: '',
        primaryText: '',
        primaryUrl: '',
        secondaryText: '',
        secondaryUrl: '',
    },
    Edit: HeroEdit,
    /**
     * @param {{eyebrow: string, title: string, lead: string, primaryText: string,
     *   primaryUrl: string, secondaryText: string, secondaryUrl: string}} props
     */
    render: (props) => {
        if (!props.title && !props.lead) {
            return '';
        }

        const eyebrow = props.eyebrow ? `<p class="builder-eyebrow">${htmlEscape(props.eyebrow)}</p>` : '';
        const title = props.title ? `<h1>${htmlEscape(props.title)}</h1>` : '';
        const lead = props.lead ? `<p class="builder-hero-lead">${htmlEscape(props.lead)}</p>` : '';
        const primary = props.primaryText
            ? `<a href="${htmlEscape(props.primaryUrl || '#')}" class="builder-cta builder-cta-primary">${htmlEscape(props.primaryText)}</a>`
            : '';
        const secondary = props.secondaryText
            ? `<a href="${htmlEscape(props.secondaryUrl || '#')}" class="builder-cta builder-cta-outline">${htmlEscape(props.secondaryText)}</a>`
            : '';
        const actions = primary || secondary ? `<div class="builder-hero-actions">${primary}${secondary}</div>` : '';

        return `<section class="builder-hero">${eyebrow}${title}${lead}${actions}</section>`;
    },
};
