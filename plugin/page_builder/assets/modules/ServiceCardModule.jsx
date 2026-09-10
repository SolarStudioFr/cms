import React from 'react';
import { Form } from 'react-bootstrap';
import IconPicker from './IconPicker';
import renderServiceCard from './serviceCard';

const COLORS = ['primary', 'secondary', 'success', 'warning', 'danger', 'info', 'light', 'dark'];

/** Admin editor for one standalone Service card block (step 48): background color, icon, title, description. */
function ServiceCardEdit({ props, onChange }) {
    return (
        <div className="d-flex flex-column gap-2">
            <div className="d-flex justify-content-between align-items-center">
                <IconPicker value={props.icon} onChange={(icon) => onChange({ ...props, icon })} />
                <Form.Select
                    size="sm"
                    style={{ maxWidth: '140px' }}
                    value={props.color}
                    onChange={(event) => onChange({ ...props, color: event.target.value })}
                >
                    {COLORS.map((color) => (
                        <option key={color} value={color}>
                            {color}
                        </option>
                    ))}
                </Form.Select>
            </div>
            <Form.Control
                size="sm"
                placeholder="Titre"
                value={props.title}
                onChange={(event) => onChange({ ...props, title: event.target.value })}
            />
            <Form.Control
                as="textarea"
                rows={2}
                placeholder="Description"
                value={props.description}
                onChange={(event) => onChange({ ...props, description: event.target.value })}
            />
        </div>
    );
}

/**
 * Registry entry for the builder's standalone Service card module (step 48):
 * same card shape as one item of the Services grid (step 42), rendered via
 * the shared ./serviceCard helper, plus a Bootstrap background color the
 * grid's cards don't have (see docs/step/MAIN.md "Notes ouvertes").
 */
export default {
    type: 'service-card',
    label: 'Service (carte seule)',
    defaultProps: { color: 'light', icon: '', title: '', description: '' },
    Edit: ServiceCardEdit,
    /** @param {{color: string, icon: string, title: string, description: string}} props */
    render: (props) => {
        if (!props.title && !props.description) {
            return '';
        }

        const color = COLORS.includes(props.color) ? props.color : 'light';
        return renderServiceCard({
            icon: props.icon,
            title: props.title,
            description: props.description,
            colorClass: `builder-service-card-bg-${color}`,
        });
    },
};
