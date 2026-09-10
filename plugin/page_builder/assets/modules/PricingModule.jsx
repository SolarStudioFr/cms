import React from 'react';
import { Button, Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import IconPicker from './IconPicker';
import renderIcon from './renderIcon';

const CARD_COUNT = 3;

/** A single, empty pricing card. */
function defaultCard() {
    return { icon: '', title: '', price: '', description: '', options: [] };
}

/** The fixed set of 3 pricing cards, all empty. */
function defaultCards() {
    return Array.from({ length: CARD_COUNT }, defaultCard);
}

/** Admin editor for one pricing card: icon, title, price, description, and a dynamically added/removed list of included options. */
function PricingCardEdit({ card, onChange }) {
    const options = card.options ?? [];

    const addOption = () => onChange({ ...card, options: [...options, ''] });
    const updateOption = (index, text) => onChange({ ...card, options: options.map((option, i) => (i === index ? text : option)) });
    const removeOption = (index) => onChange({ ...card, options: options.filter((_, i) => i !== index) });

    return (
        <div className="border rounded p-2 d-flex flex-column gap-2">
            <IconPicker value={card.icon} onChange={(icon) => onChange({ ...card, icon })} />
            <Form.Control
                size="sm"
                placeholder="Titre"
                value={card.title}
                onChange={(event) => onChange({ ...card, title: event.target.value })}
            />
            <Form.Control
                size="sm"
                placeholder="Tarif"
                value={card.price}
                onChange={(event) => onChange({ ...card, price: event.target.value })}
            />
            <Form.Control
                as="textarea"
                rows={2}
                placeholder="Description"
                value={card.description}
                onChange={(event) => onChange({ ...card, description: event.target.value })}
            />

            {options.map((option, index) => (
                <div key={index} className="d-flex align-items-center gap-2">
                    <Form.Control
                        size="sm"
                        placeholder="Option incluse"
                        value={option}
                        onChange={(event) => updateOption(index, event.target.value)}
                    />
                    <Button size="sm" variant="outline-danger" onClick={() => removeOption(index)}>
                        Retirer
                    </Button>
                </div>
            ))}
            <Button size="sm" variant="outline-secondary" onClick={addOption} className="align-self-start">
                Ajouter une option
            </Button>
        </div>
    );
}

/**
 * Admin editor for one Pricing block (step 49): a *fixed* set of 3 cards
 * (no add/remove, same convention as ProcessStepsModule/step 43), each with
 * its own dynamically added/removed list of included options (same nested-
 * list pattern as TrustedByModule/ServicesGridModule, steps 41/42).
 */
function PricingEdit({ props, onChange }) {
    const cards = props.cards && CARD_COUNT === props.cards.length ? props.cards : defaultCards();

    const updateCard = (index, card) => onChange({ ...props, cards: cards.map((c, i) => (i === index ? card : c)) });

    return (
        <div className="d-flex flex-column gap-3">
            {cards.map((card, index) => (
                <PricingCardEdit key={index} card={card} onChange={(next) => updateCard(index, next)} />
            ))}
        </div>
    );
}

/** Registry entry for the builder's Pricing module (step 49). */
export default {
    type: 'pricing',
    label: 'Tarifs',
    defaultProps: { cards: defaultCards() },
    Edit: PricingEdit,
    /**
     * @param {{cards: {icon: string, title: string, price: string, description: string, options: string[]}[]}} props
     */
    render: (props) => {
        const cards = (props.cards && CARD_COUNT === props.cards.length ? props.cards : defaultCards()).filter(
            (card) => card.title || card.price || card.description || (card.options ?? []).some(Boolean),
        );
        if (0 === cards.length) {
            return '';
        }

        const cardsHtml = cards
            .map((card) => {
                const options = (card.options ?? []).filter(Boolean);
                const optionsList = options.length
                    ? `<ul class="builder-pricing-options">${options.map((option) => `<li>${htmlEscape(option)}</li>`).join('')}</ul>`
                    : '';

                return (
                    `<div class="builder-pricing-card">` +
                    `<div class="builder-service-icon">${renderIcon(card.icon)}</div>` +
                    (card.title ? `<h3>${htmlEscape(card.title)}</h3>` : '') +
                    (card.price ? `<p class="builder-pricing-price">${htmlEscape(card.price)}</p>` : '') +
                    (card.description ? `<p>${htmlEscape(card.description)}</p>` : '') +
                    optionsList +
                    `</div>`
                );
            })
            .join('');

        return `<section class="builder-pricing"><div class="builder-pricing-grid">${cardsHtml}</div></section>`;
    },
};
