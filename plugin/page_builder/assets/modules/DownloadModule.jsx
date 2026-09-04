import React, { Suspense, lazy, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';

// Consumed from the admin host's Module Federation remote (step 09), lazy
// since resolving a cross-container remote is inherently async.
const MediaPicker = lazy(() => import('adm_host/MediaPicker'));

/** Admin editor for one Download block (step 13): pick a PDF/ZIP via the shared media picker, edit its button label. */
function DownloadEdit({ props, onChange }) {
    const [pickerOpen, setPickerOpen] = useState(false);

    return (
        <div>
            {props.fileName ? (
                <p className="small mb-2">
                    Fichier : <strong>{props.fileName}</strong>
                </p>
            ) : (
                <p className="text-muted small">Aucun fichier sélectionné.</p>
            )}
            <Button size="sm" variant="outline-secondary" className="mb-2" onClick={() => setPickerOpen(true)}>
                {props.fileUrl ? 'Changer le fichier' : 'Choisir un fichier'}
            </Button>
            <Form.Control
                size="sm"
                placeholder="Texte du bouton"
                value={props.label}
                onChange={(event) => onChange({ ...props, label: event.target.value })}
            />
            {pickerOpen && (
                <Suspense fallback={null}>
                    <MediaPicker
                        show={pickerOpen}
                        onHide={() => setPickerOpen(false)}
                        onSelect={(file) => onChange({ ...props, fileUrl: file.url, fileName: file.name })}
                        types={['pdf', 'zip']}
                        title="Choisir un fichier à télécharger"
                    />
                </Suspense>
            )}
        </div>
    );
}

/** Registry entry for the builder's "download a file" module (step 13, PDF/ZIP only via the media picker). */
export default {
    type: 'download',
    label: 'Télécharger un fichier',
    defaultProps: { fileUrl: '', fileName: '', label: 'Télécharger' },
    Edit: DownloadEdit,
    /** @param {{fileUrl: string, fileName: string, label: string}} props */
    render: (props) =>
        props.fileUrl
            ? `<a href="${htmlEscape(props.fileUrl)}" download="${htmlEscape(props.fileName)}" class="builder-download">${htmlEscape(props.label || 'Télécharger')}</a>`
            : '',
};
