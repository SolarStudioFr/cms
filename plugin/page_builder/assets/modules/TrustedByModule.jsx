import React from 'react';
import { Button, Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import useDomainTranslator from '../useDomainTranslator';

/**
 * Admin editor for one "trusted by" block (step 41): an intro text plus a
 * dynamically added/removed list of client names (text, not image logos -
 * `../MAIN.md`/../../NEW_BLOCK_PAGE_BUILDER.md both read "logos texte" as
 * plain client-name text, not a media-picker upload).
 */
function TrustedByEdit({ props, onChange }) {
    const { t } = useDomainTranslator('page_builder');
    const clients = props.clients ?? [];

    const addClient = () => onChange({ ...props, clients: [...clients, ''] });
    const updateClient = (index, name) =>
        onChange({ ...props, clients: clients.map((client, i) => (i === index ? name : client)) });
    const removeClient = (index) => onChange({ ...props, clients: clients.filter((_, i) => i !== index) });

    return (
        <div className="d-flex flex-column gap-2">
            <Form.Control
                as="textarea"
                rows={2}
                placeholder={t('module.introText')}
                value={props.intro}
                onChange={(event) => onChange({ ...props, intro: event.target.value })}
            />

            {clients.map((client, index) => (
                <div key={index} className="d-flex align-items-center gap-2">
                    <Form.Control
                        size="sm"
                        placeholder={t('module.trustedBy.clientName')}
                        value={client}
                        onChange={(event) => updateClient(index, event.target.value)}
                    />
                    <Button size="sm" variant="outline-danger" onClick={() => removeClient(index)}>
                        {t('module.remove')}
                    </Button>
                </div>
            ))}
            <Button size="sm" variant="outline-secondary" onClick={addClient} className="align-self-start">
                {t('module.trustedBy.addClient')}
            </Button>
        </div>
    );
}

/** Registry entry for the builder's "trusted by" module (step 41). */
export default {
    type: 'trusted-by',
    label: 'module.trustedBy.label',
    defaultProps: { intro: '', clients: [] },
    Edit: TrustedByEdit,
    /** @param {{intro: string, clients: string[]}} props */
    render: (props) => {
        const clients = (props.clients ?? []).filter(Boolean);
        if (!props.intro && 0 === clients.length) {
            return '';
        }

        const intro = props.intro ? `<p class="builder-trusted-by-intro">${htmlEscape(props.intro)}</p>` : '';
        const items = clients.map((client) => `<li class="builder-trusted-by-item">${htmlEscape(client)}</li>`).join('');
        const list = items ? `<ul class="builder-trusted-by-list">${items}</ul>` : '';

        return `<section class="builder-trusted-by">${intro}${list}</section>`;
    },
};
