import React, { Suspense, lazy, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import useDomainTranslator from '../useDomainTranslator';

// Consumed from the admin host's Module Federation remote (step 09), lazy
// since resolving a cross-container remote is inherently async.
const MediaPicker = lazy(() => import('adm_host/MediaPicker'));

/** Admin editor for one Download block (step 13): pick a PDF/ZIP via the shared media picker, edit its button label. */
function DownloadEdit({ props, onChange }) {
    const { t } = useDomainTranslator('page_builder');
    const [pickerOpen, setPickerOpen] = useState(false);

    return (
        <div>
            {props.fileName ? (
                <p className="small mb-2">
                    {t('module.download.file')} <strong>{props.fileName}</strong>
                </p>
            ) : (
                <p className="text-muted small">{t('module.download.noFile')}</p>
            )}
            <Button size="sm" variant="outline-secondary" className="mb-2" onClick={() => setPickerOpen(true)}>
                {props.fileUrl ? t('module.download.changeFile') : t('module.download.chooseFile')}
            </Button>
            <Form.Control
                size="sm"
                placeholder={t('module.buttonText')}
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
                        title={t('module.download.pickerTitle')}
                    />
                </Suspense>
            )}
        </div>
    );
}

/** Registry entry for the builder's "download a file" module (step 13, PDF/ZIP only via the media picker). */
export default {
    type: 'download',
    label: 'module.download.label',
    defaultProps: { fileUrl: '', fileName: '', label: 'Télécharger' },
    Edit: DownloadEdit,
    /** @param {{fileUrl: string, fileName: string, label: string}} props */
    render: (props) =>
        props.fileUrl
            ? `<a href="${htmlEscape(props.fileUrl)}" download="${htmlEscape(props.fileName)}" class="builder-download">${htmlEscape(props.label || 'Télécharger')}</a>`
            : '',
};
