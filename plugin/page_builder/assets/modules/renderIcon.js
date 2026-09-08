import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { getIconComponent } from './icons';

/**
 * Renders one IconPicker selection to a static SVG string (step 42), for
 * embedding directly into the HTML a content plugin stores/serves publicly.
 * This runs client-side (renderToHtml executes in the admin bundle at save
 * time, never on the public theme), so `react-dom/server` here just means
 * "produce a markup string from a React element", not real SSR.
 *
 * @param {string} name an IconPicker selection (see modules/icons.js), or falsy
 * @returns {string} the icon's SVG markup, or "" if unset/unknown
 */
export default function renderIcon(name) {
    const Icon = name && getIconComponent(name);
    return Icon ? renderToStaticMarkup(React.createElement(Icon, { className: 'builder-icon', 'aria-hidden': 'true' })) : '';
}
