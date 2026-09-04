import React, { Suspense, lazy } from 'react';

// Consumed from the admin host's Module Federation remote (step 09), lazy
// since resolving a cross-container remote is inherently async. `full`
// switches it to the richer toolbar (font, color, alignment, code block)
// appropriate for this, the builder's most complex module.
const RichTextEditor = lazy(() => import('adm_host/RichTextEditor'));

/** Admin editor for one Text block (step 15): the full WYSIWYG editor, HTML output. */
function TextEdit({ props, onChange }) {
    return (
        <Suspense fallback={<p className="text-muted small mb-0">Chargement de l'éditeur...</p>}>
            <RichTextEditor
                value={props.html}
                onChange={(html) => onChange({ ...props, html })}
                placeholder="Texte..."
                full
            />
        </Suspense>
    );
}

/** Registry entry for the builder's Text module (step 15) - full WYSIWYG, the most complex module. */
export default {
    type: 'text',
    label: 'Texte',
    defaultProps: { html: '' },
    Edit: TextEdit,
    // The editor already outputs sanitized-by-Quill HTML meant to be
    // rendered as-is (same contract as RichTextEditor's own consumers,
    // e.g. Page.content) - no htmlEscape() here, unlike modules that
    // interpolate plain-text fields (alt text, labels) into markup.
    render: (props) => (props.html ? `<div class="builder-text">${props.html}</div>` : ''),
};
