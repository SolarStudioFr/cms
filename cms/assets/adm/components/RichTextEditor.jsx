import React, { useEffect, useRef, useState } from 'react';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import MediaPicker from './MediaPicker';
import { useTranslator } from '../i18n/TranslationContext';

const SIMPLE_TOOLBAR = [
    [{ header: [2, 3, false] }],
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote', 'link', 'image'],
    ['clean'],
];

// Fuller formatting set for the builder's Text module (step 15) - the
// "most complex module", explicitly wants font choice and richer
// formatting beyond the fallback editor's deliberately modest toolbar.
const FULL_TOOLBAR = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ font: [] }],
    [{ align: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote', 'code-block', 'link', 'image'],
    ['clean'],
];

/**
 * WYSIWYG editor built on vanilla Quill (not react-quill: it relies on
 * React's removed findDOMNode API and breaks outright under React 19).
 * Two roles share this same component:
 * - the simple fallback editor (step 09), used by default by any content
 *   plugin until the builder is active;
 * - the builder's Text module (step 15, `full`), its most complex module.
 *
 * Controlled at the boundary only: `value` seeds the editor once on mount
 * (the caller must not render this before its initial value is known, e.g.
 * wait past an async load), further edits are reported via `onChange` -
 * re-syncing `value` on every keystroke would fight the user's cursor.
 *
 * @param {string} value initial HTML content
 * @param {(html: string) => void} onChange called with the editor's HTML on every edit
 * @param {string} [placeholder]
 * @param {boolean} [full] use the fuller toolbar (font, color, alignment, code block) instead of the simple one
 */
export default function RichTextEditor({ value, onChange, placeholder, full = false }) {
    const { t } = useTranslator();
    const containerRef = useRef(null);
    const quillRef = useRef(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;
    const [pickerOpen, setPickerOpen] = useState(false);

    useEffect(() => {
        const container = containerRef.current;

        // Quill mutates whatever node it's given in place (adds classes,
        // inserts a toolbar as a sibling) rather than cleanly appending under
        // it, so passing `container` itself would leave stray DOM behind
        // that a `quillRef.current = null` cleanup can't undo - under
        // React.StrictMode's dev-only double-invoke of effects, that left
        // two toolbars stacked on the same container. Mounting on a fresh
        // child element instead means cleanup can just wipe the container.
        const editorRoot = container.appendChild(document.createElement('div'));

        const quill = new Quill(editorRoot, {
            theme: 'snow',
            placeholder,
            modules: {
                toolbar: {
                    container: full ? FULL_TOOLBAR : SIMPLE_TOOLBAR,
                    handlers: {
                        // Route the image button through the shared media picker
                        // (step 03) instead of Quill's default raw file prompt.
                        image: () => setPickerOpen(true),
                    },
                },
            },
        });
        quillRef.current = quill;

        if (value) {
            quill.clipboard.dangerouslyPasteHTML(value);
        }

        quill.on('text-change', () => {
            onChangeRef.current(quill.root.innerHTML);
        });

        return () => {
            quillRef.current = null;
            container.innerHTML = '';
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // `placeholder` is only known once the caller's translation catalog has
    // loaded (a plugin's useDomainTranslator fetches it async, and may still
    // resolve after this editor mounts), so it can arrive after the effect
    // above already constructed Quill with the untranslated key as text.
    // Updating the DOM attribute Quill itself reads for the placeholder
    // keeps this in sync without re-seeding `value` and fighting the cursor.
    useEffect(() => {
        quillRef.current?.root.setAttribute('data-placeholder', placeholder ?? '');
    }, [placeholder]);

    const insertImage = (file) => {
        const quill = quillRef.current;
        if (!quill) {
            return;
        }
        const index = quill.getSelection(true)?.index ?? quill.getLength();
        quill.insertEmbed(index, 'image', file.url, 'user');
        quill.setSelection(index + 1);
    };

    return (
        <>
            <div ref={containerRef} style={{ minHeight: '220px', background: '#fff' }} />
            <MediaPicker
                show={pickerOpen}
                onHide={() => setPickerOpen(false)}
                onSelect={insertImage}
                types={['img']}
                title={t('richTextEditor.insertImage')}
            />
        </>
    );
}
