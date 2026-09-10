import React from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';

const LEVELS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

/** Admin editor for one Heading block (step 46): heading level (h1-h6) + text. */
function HeadingEdit({ props, onChange }) {
    return (
        <div className="d-flex gap-2">
            <Form.Select
                size="sm"
                style={{ maxWidth: '90px' }}
                value={props.level}
                onChange={(event) => onChange({ ...props, level: event.target.value })}
            >
                {LEVELS.map((level) => (
                    <option key={level} value={level}>
                        {level.toUpperCase()}
                    </option>
                ))}
            </Form.Select>
            <Form.Control
                placeholder="Texte du titre"
                value={props.text}
                onChange={(event) => onChange({ ...props, text: event.target.value })}
            />
        </div>
    );
}

/** Registry entry for the builder's Heading module (step 46). */
export default {
    type: 'heading',
    label: 'Titre',
    defaultProps: { level: 'h2', text: '' },
    Edit: HeadingEdit,
    /**
     * `level` is whitelisted rather than interpolated as-is: it drives the
     * actual HTML tag name, and while the admin editor only ever offers the
     * 6 valid values via a <select>, a stored block predating this
     * whitelist (or a hand-edited JSON value) must not be able to emit an
     * arbitrary tag.
     *
     * @param {{level: string, text: string}} props
     */
    render: (props) => {
        if (!props.text) {
            return '';
        }

        const level = LEVELS.includes(props.level) ? props.level : 'h2';
        return `<${level} class="builder-heading">${htmlEscape(props.text)}</${level}>`;
    },
};
