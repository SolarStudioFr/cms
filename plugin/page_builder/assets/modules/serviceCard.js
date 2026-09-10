import htmlEscape from './htmlEscape';
import renderIcon from './renderIcon';

/**
 * Shared markup for one "service card" (icon + title + description) - the
 * Services grid module (step 42) and the standalone Service card module
 * (step 48) render the exact same card shape, the latter simply adding an
 * optional Bootstrap background color the grid doesn't have. Extracted here
 * rather than duplicated, per the shared-card decision deferred to the
 * start of step 48 (see "Notes ouvertes" in docs/step/MAIN.md).
 *
 * @param {{icon: string, title: string, description: string, colorClass?: string}} card
 * @returns {string}
 */
export default function renderServiceCard({ icon, title, description, colorClass }) {
    const classes = colorClass ? `builder-service-card ${colorClass}` : 'builder-service-card';

    return (
        `<div class="${classes}">` +
        `<div class="builder-service-icon">${renderIcon(icon)}</div>` +
        `<h3>${htmlEscape(title)}</h3>` +
        `<p>${htmlEscape(description)}</p>` +
        `</div>`
    );
}
