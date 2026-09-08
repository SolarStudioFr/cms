import React from 'react';
import { Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';

const STEP_COUNT = 4;

function defaultSteps() {
    return Array.from({ length: STEP_COUNT }, (_, index) => ({ number: String(index + 1).padStart(2, '0'), title: '', description: '' }));
}

/**
 * Admin editor for one "Our approach" block (step 43): eyebrow, title, and a
 * *fixed* set of 4 step cards (number label, title, description) - no
 * add/remove button, unlike the dynamic lists of 41/42/49.
 */
function ProcessStepsEdit({ props, onChange }) {
    // Defensive against a block created before this file existed, or any
    // future manual edit of the stored JSON - always render exactly 4 rows.
    const steps = props.steps && props.steps.length === STEP_COUNT ? props.steps : defaultSteps();

    const updateStep = (index, field, value) =>
        onChange({ ...props, steps: steps.map((step, i) => (i === index ? { ...step, [field]: value } : step)) });

    return (
        <div className="d-flex flex-column gap-2">
            <Form.Control
                size="sm"
                placeholder="Eyebrow"
                value={props.eyebrow}
                onChange={(event) => onChange({ ...props, eyebrow: event.target.value })}
            />
            <Form.Control
                placeholder="Titre"
                value={props.title}
                onChange={(event) => onChange({ ...props, title: event.target.value })}
            />

            {steps.map((step, index) => (
                <div key={index} className="border rounded p-2 d-flex flex-column gap-2">
                    <Form.Control
                        size="sm"
                        style={{ maxWidth: '100px' }}
                        placeholder="N°"
                        value={step.number}
                        onChange={(event) => updateStep(index, 'number', event.target.value)}
                    />
                    <Form.Control
                        size="sm"
                        placeholder="Titre de l'étape"
                        value={step.title}
                        onChange={(event) => updateStep(index, 'title', event.target.value)}
                    />
                    <Form.Control
                        as="textarea"
                        rows={2}
                        placeholder="Description"
                        value={step.description}
                        onChange={(event) => updateStep(index, 'description', event.target.value)}
                    />
                </div>
            ))}
        </div>
    );
}

/** Registry entry for the builder's "Our approach" module (step 43). */
export default {
    type: 'process-steps',
    label: 'Notre approche',
    defaultProps: { eyebrow: '', title: '', steps: defaultSteps() },
    Edit: ProcessStepsEdit,
    /** @param {{eyebrow: string, title: string, steps: {number: string, title: string, description: string}[]}} props */
    render: (props) => {
        const steps = (props.steps ?? []).filter((step) => step.title || step.description);
        if (!props.title && 0 === steps.length) {
            return '';
        }

        const eyebrow = props.eyebrow ? `<p class="builder-eyebrow">${htmlEscape(props.eyebrow)}</p>` : '';
        const title = props.title ? `<h2>${htmlEscape(props.title)}</h2>` : '';
        const header = eyebrow || title ? `<div class="builder-section-header">${eyebrow}${title}</div>` : '';

        const cards = steps
            .map(
                (step) =>
                    `<div class="builder-process-step">` +
                    `<span class="builder-process-step-number">${htmlEscape(step.number)}</span>` +
                    `<h3>${htmlEscape(step.title)}</h3>` +
                    `<p>${htmlEscape(step.description)}</p>` +
                    `</div>`,
            )
            .join('');
        const list = cards ? `<div class="builder-process-steps">${cards}</div>` : '';

        return `<section class="builder-process">${header}${list}</section>`;
    },
};
