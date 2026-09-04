import { getModule } from './modules/registry';

/**
 * Converts a BuilderCanvas JSON value into the HTML a consuming plugin
 * stores/serves publicly (step 10) - a pure function, no React/DOM
 * dependency, so it's cheap for a consumer to call at save time. Unknown
 * module types (e.g. from a since-uninstalled module) are silently
 * skipped rather than breaking the whole page's output.
 *
 * @param {string} json a BuilderCanvas value, or "" / invalid JSON
 * @returns {string}
 */
export default function renderToHtml(json) {
    let modules = [];

    try {
        modules = json ? (JSON.parse(json).modules ?? []) : [];
    } catch {
        return '';
    }

    return modules
        .map((block) => getModule(block.type)?.render(block.props) ?? '')
        .filter(Boolean)
        .join('\n');
}
