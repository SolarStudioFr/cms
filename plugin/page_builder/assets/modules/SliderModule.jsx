import React, { Suspense, lazy, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import htmlEscape from './htmlEscape';

// Consumed from the admin host's Module Federation remote (step 09), lazy
// since resolving a cross-container remote is inherently async.
const MediaPicker = lazy(() => import('adm_host/MediaPicker'));

/** Admin editor for one Slider block (step 12): an ordered list of images, added/removed individually. */
function SliderEdit({ props, onChange }) {
    const [pickerOpen, setPickerOpen] = useState(false);
    const images = props.images ?? [];

    const addImage = (file) => {
        onChange({ ...props, images: [...images, { fileUrl: file.url, alt: file.name }] });
    };

    const removeImage = (index) => {
        onChange({ ...props, images: images.filter((_, i) => i !== index) });
    };

    const updateAlt = (index, alt) => {
        onChange({ ...props, images: images.map((image, i) => (i === index ? { ...image, alt } : image)) });
    };

    return (
        <div>
            {0 === images.length && <p className="text-muted small">Aucune image dans ce slider.</p>}
            {images.map((image, index) => (
                <div key={index} className="d-flex align-items-center gap-2 mb-2">
                    <img
                        src={image.fileUrl}
                        alt={image.alt}
                        style={{ width: '56px', height: '56px', objectFit: 'cover', flexShrink: 0 }}
                    />
                    <Form.Control
                        size="sm"
                        value={image.alt}
                        placeholder="Texte alternatif"
                        onChange={(event) => updateAlt(index, event.target.value)}
                    />
                    <Button size="sm" variant="outline-danger" onClick={() => removeImage(index)}>
                        Retirer
                    </Button>
                </div>
            ))}
            <Button size="sm" variant="outline-secondary" onClick={() => setPickerOpen(true)}>
                Ajouter une image
            </Button>
            {pickerOpen && (
                <Suspense fallback={null}>
                    <MediaPicker
                        show={pickerOpen}
                        onHide={() => setPickerOpen(false)}
                        onSelect={addImage}
                        types={['img']}
                        title="Ajouter une image au slider"
                    />
                </Suspense>
            )}
        </div>
    );
}

/** Registry entry for the builder's Slider module (step 12). */
export default {
    type: 'slider',
    label: 'Slider',
    defaultProps: { images: [] },
    Edit: SliderEdit,
    /**
     * Renders a horizontally-scrolling image strip - dependency-free (no
     * Bootstrap JS carousel behavior required at runtime, unlike a
     * data-bs-* carousel, since the public theme doesn't load bootstrap.js).
     *
     * @param {{images: {fileUrl: string, alt: string}[]}} props
     */
    render: (props) => {
        const images = props.images ?? [];
        if (0 === images.length) {
            return '';
        }

        const items = images
            .map((image) => `<img src="${htmlEscape(image.fileUrl)}" alt="${htmlEscape(image.alt)}" class="builder-slider-item" />`)
            .join('');

        return `<div class="builder-slider">${items}</div>`;
    },
};
