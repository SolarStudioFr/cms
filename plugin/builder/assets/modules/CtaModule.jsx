import React from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';

const STYLES = ['primary', 'secondary', 'outline'];

/** Admin editor for one CTA block (step 14): button text, link target, visual style. */
function CtaEdit({ props, onChange }) {
    return (
        <div className="d-flex flex-column gap-2">
            <Form.Control
                size="sm"
                placeholder="Texte du bouton"
                value={props.text}
                onChange={(event) => onChange({ ...props, text: event.target.value })}
            />
            <Form.Control
                size="sm"
                placeholder="Lien (URL)"
                value={props.url}
                onChange={(event) => onChange({ ...props, url: event.target.value })}
            />
            <Form.Select
                size="sm"
                value={props.style}
                onChange={(event) => onChange({ ...props, style: event.target.value })}
                style={{ maxWidth: '160px' }}
            >
                {STYLES.map((style) => (
                    <option key={style} value={style}>
                        {style}
                    </option>
                ))}
            </Form.Select>
        </div>
    );
}

/** Registry entry for the builder's call-to-action module (step 14). */
export default {
    type: 'cta',
    label: 'Appel à l’action',
    defaultProps: { text: 'En savoir plus', url: '', style: 'primary' },
    Edit: CtaEdit,
    /** @param {{text: string, url: string, style: string}} props */
    render: (props) =>
        props.text
            ? `<a href="${htmlEscape(props.url || '#')}" class="builder-cta builder-cta-${htmlEscape(props.style || 'primary')}">${htmlEscape(props.text)}</a>`
            : '',
};
