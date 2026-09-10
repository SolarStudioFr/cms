import React from 'react';
import { Button, Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import IconPicker from './IconPicker';
import renderServiceCard from './serviceCard';

/** Admin editor for one service row: icon (searchable picker), title, description, remove button. */
function ServiceRow({ service, onChange, onRemove }) {
    return (
        <div className="border rounded p-2 mb-2 d-flex flex-column gap-2">
            <div className="d-flex justify-content-between align-items-center">
                <IconPicker value={service.icon} onChange={(icon) => onChange({ ...service, icon })} />
                <Button size="sm" variant="outline-danger" onClick={onRemove}>
                    Retirer
                </Button>
            </div>
            <Form.Control
                size="sm"
                placeholder="Titre du service"
                value={service.title}
                onChange={(event) => onChange({ ...service, title: event.target.value })}
            />
            <Form.Control
                as="textarea"
                rows={2}
                placeholder="Description"
                value={service.description}
                onChange={(event) => onChange({ ...service, description: event.target.value })}
            />
        </div>
    );
}

/** Admin editor for one Services grid block (step 42): section header + a dynamically added list of services. */
function ServicesGridEdit({ props, onChange }) {
    const services = props.services ?? [];

    const addService = () =>
        onChange({ ...props, services: [...services, { icon: '', title: '', description: '' }] });
    const updateService = (index, service) =>
        onChange({ ...props, services: services.map((s, i) => (i === index ? service : s)) });
    const removeService = (index) => onChange({ ...props, services: services.filter((_, i) => i !== index) });

    return (
        <div className="d-flex flex-column gap-2">
            <Form.Control
                size="sm"
                placeholder="Eyebrow"
                value={props.eyebrow}
                onChange={(event) => onChange({ ...props, eyebrow: event.target.value })}
            />
            <Form.Control
                placeholder="Titre de section"
                value={props.title}
                onChange={(event) => onChange({ ...props, title: event.target.value })}
            />
            <Form.Control
                as="textarea"
                rows={2}
                placeholder="Texte d'introduction"
                value={props.lead}
                onChange={(event) => onChange({ ...props, lead: event.target.value })}
            />

            {services.map((service, index) => (
                <ServiceRow
                    key={index}
                    service={service}
                    onChange={(next) => updateService(index, next)}
                    onRemove={() => removeService(index)}
                />
            ))}
            <Button size="sm" variant="outline-secondary" onClick={addService} className="align-self-start">
                Ajouter un service
            </Button>
        </div>
    );
}

/** Registry entry for the builder's Services grid module (step 42). Card markup shared with the standalone Service card module (step 48) via ./serviceCard. */
export default {
    type: 'services-grid',
    label: 'Grille des services',
    defaultProps: { eyebrow: '', title: '', lead: '', services: [] },
    Edit: ServicesGridEdit,
    /**
     * @param {{eyebrow: string, title: string, lead: string,
     *   services: {icon: string, title: string, description: string}[]}} props
     */
    render: (props) => {
        const services = (props.services ?? []).filter((service) => service.title || service.description);
        if (!props.title && !props.lead && 0 === services.length) {
            return '';
        }

        const eyebrow = props.eyebrow ? `<p class="builder-eyebrow">${htmlEscape(props.eyebrow)}</p>` : '';
        const title = props.title ? `<h2>${htmlEscape(props.title)}</h2>` : '';
        const lead = props.lead ? `<p class="builder-lead">${htmlEscape(props.lead)}</p>` : '';
        const header = eyebrow || title || lead ? `<div class="builder-section-header">${eyebrow}${title}${lead}</div>` : '';

        const cards = services.map((service) => renderServiceCard(service)).join('');
        const grid = cards ? `<div class="builder-services-grid">${cards}</div>` : '';

        return `<section class="builder-services">${header}${grid}</section>`;
    },
};
