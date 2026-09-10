import React, { Suspense, lazy, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';
import useDomainTranslator from '../useDomainTranslator';

// Consumed from the admin host's Module Federation remote (step 09), lazy
// since resolving a cross-container remote is inherently async.
const MediaPicker = lazy(() => import('adm_host/MediaPicker'));

/** Admin editor for one Image block (step 11): pick a file via the shared media picker, set alt text. */
function ImageEdit({ props, onChange }) {
    const { t } = useDomainTranslator('page_builder');
    const [pickerOpen, setPickerOpen] = useState(false);

    return (
        <div>
            {props.fileUrl ? (
                <img
                    src={props.fileUrl}
                    alt={props.alt}
                    style={{ maxWidth: '240px', maxHeight: '160px', display: 'block', marginBottom: '8px' }}
                />
            ) : (
                <p className="text-muted small">{t('module.noImageSelected')}</p>
            )}
            <Button size="sm" variant="outline-secondary" className="mb-2" onClick={() => setPickerOpen(true)}>
                {props.fileUrl ? t('module.changeImage') : t('module.chooseImage')}
            </Button>
            <Form.Control
                size="sm"
                placeholder={t('module.altText')}
                value={props.alt}
                onChange={(event) => onChange({ ...props, alt: event.target.value })}
            />
            {pickerOpen && (
                <Suspense fallback={null}>
                    <MediaPicker
                        show={pickerOpen}
                        onHide={() => setPickerOpen(false)}
                        onSelect={(file) => onChange({ ...props, fileUrl: file.url, alt: props.alt || file.name })}
                        types={['img']}
                        title={t('module.chooseImageTitle')}
                    />
                </Suspense>
            )}
        </div>
    );
}

/** Registry entry for the builder's Image module (step 11). */
export default {
    type: 'image',
    label: 'module.image.label',
    defaultProps: { fileUrl: '', alt: '' },
    Edit: ImageEdit,
    /** @param {{fileUrl: string, alt: string}} props */
    render: (props) =>
        props.fileUrl
            ? `<img src="${htmlEscape(props.fileUrl)}" alt="${htmlEscape(props.alt)}" class="builder-image" />`
            : '',
};
