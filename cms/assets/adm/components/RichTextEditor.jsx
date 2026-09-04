import React, { useEffect, useRef, useState } from 'react';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import MediaPicker from './MediaPicker';

/**
 * Simple WYSIWYG fallback editor (step 09), used by default by any content
 * plugin (page, réalisations, actualités, accueil) until the drag & drop
 * builder plugin is installed/active. A thin wrapper around vanilla Quill
 * rather than react-quill: react-quill relies on React's removed
 * findDOMNode API and breaks outright under React 19.
 *
 * Controlled at the boundary only: `value` seeds the editor once on mount
 * (the caller must not render this before its initial value is known, e.g.
 * wait past an async load), further edits are reported via `onChange` -
 * re-syncing `value` on every keystroke would fight the user's cursor.
 *
 * @param {string} value initial HTML content
 * @param {(html: string) => void} onChange called with the editor's HTML on every edit
 * @param {string} [placeholder]
 */
export default function RichTextEditor({ value, onChange, placeholder }) {
    const containerRef = useRef(null);
    const quillRef = useRef(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;
    const [pickerOpen, setPickerOpen] = useState(false);

    useEffect(() => {
        const quill = new Quill(containerRef.current, {
            theme: 'snow',
            placeholder,
            modules: {
                toolbar: {
                    container: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['blockquote', 'link', 'image'],
                        ['clean'],
                    ],
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
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

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
                title="Insérer une image"
            />
        </>
    );
}
