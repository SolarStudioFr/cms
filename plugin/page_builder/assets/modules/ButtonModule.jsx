import React from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import useDomainTranslator from '../useDomainTranslator';

const COLORS = ['primary', 'secondary', 'success', 'warning', 'danger', 'info', 'light', 'dark'];
const SIZES = { small: 'module.button.sizeSmall', medium: 'module.button.sizeMedium', large: 'module.button.sizeLarge' };
const TARGETS = { self: 'module.button.targetSelf', blank: 'module.button.targetBlank' };

/** Admin editor for one Button block (step 47): label, URL, Bootstrap color, size, link target. */
function ButtonEdit({ props, onChange }) {
    const { t } = useDomainTranslator('page_builder');

    return (
        <div className="d-flex flex-column gap-2">
            <Form.Control
                size="sm"
                placeholder={t('module.buttonText')}
                value={props.text}
                onChange={(event) => onChange({ ...props, text: event.target.value })}
            />
            <Form.Control
                size="sm"
                placeholder="URL"
                value={props.url}
                onChange={(event) => onChange({ ...props, url: event.target.value })}
            />
            <div className="d-flex gap-2">
                <Form.Select size="sm" value={props.color} onChange={(event) => onChange({ ...props, color: event.target.value })}>
                    {COLORS.map((color) => (
                        <option key={color} value={color}>
                            {color}
                        </option>
                    ))}
                </Form.Select>
                <Form.Select size="sm" value={props.size} onChange={(event) => onChange({ ...props, size: event.target.value })}>
                    {Object.entries(SIZES).map(([value, key]) => (
                        <option key={value} value={value}>
                            {t(key)}
                        </option>
                    ))}
                </Form.Select>
                <Form.Select size="sm" value={props.target} onChange={(event) => onChange({ ...props, target: event.target.value })}>
                    {Object.entries(TARGETS).map(([value, key]) => (
                        <option key={value} value={value}>
                            {t(key)}
                        </option>
                    ))}
                </Form.Select>
            </div>
        </div>
    );
}

/** Registry entry for the builder's Button module (step 47). */
export default {
    type: 'button',
    label: 'module.button.label',
    defaultProps: { text: '', url: '', color: 'primary', size: 'medium', target: 'self' },
    Edit: ButtonEdit,
    /**
     * Renders the button with Bootstrap's own `btn`/`btn-{color}`/`btn-{sm,lg}`
     * classes (already loaded site-wide, see CLAUDE.md's stack notes) rather
     * than a bespoke `builder-*` class needing its own CSS, unlike the rest
     * of the plugin's modules - this is the one module whose visual variants
     * map 1:1 onto an existing Bootstrap primitive.
     *
     * @param {{text: string, url: string, color: string, size: 'small'|'medium'|'large', target: 'self'|'blank'}} props
     */
    render: (props) => {
        if (!props.text) {
            return '';
        }

        const color = COLORS.includes(props.color) ? props.color : 'primary';
        const sizeClass = 'small' === props.size ? ' btn-sm' : 'large' === props.size ? ' btn-lg' : '';
        const targetAttr = 'blank' === props.target ? ' target="_blank" rel="noopener noreferrer"' : '';

        return (
            `<div class="builder-button-wrap">` +
            `<a href="${htmlEscape(props.url || '#')}" class="btn btn-${color}${sizeClass}"${targetAttr}>${htmlEscape(props.text)}</a>` +
            `</div>`
        );
    },
};
