const ENTITIES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

/** Escapes a value for safe interpolation into an HTML attribute or text node. */
export default function htmlEscape(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ENTITIES[char]);
}
