import React, { useRef, useState } from 'react';
import { Button, Form, Spinner } from 'react-bootstrap';
import client from '../api/client';

/** Native file input "accept" hint per File type - server-side still validates the real mime type. */
const ACCEPT_BY_TYPE = {
    img: 'image/*',
    pdf: 'application/pdf',
    zip: 'application/zip',
};

/**
 * Upload form shared by the full-page file manager (step 02) and the media
 * picker modal (step 03). Posts to the step-02 admin endpoint and reports
 * the newly created File back via `onUploaded`.
 */
export default function FileUploadForm({ types, onUploaded }) {
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState(null);
    const fileInputRef = useRef(null);

    const accept = types?.length ? types.map((type) => ACCEPT_BY_TYPE[type]).join(',') : undefined;

    const handleSubmit = async (event) => {
        event.preventDefault();
        // MediaPicker (and this form with it) can be opened from inside
        // another <Form> (e.g. PageForm around the builder). React bubbles
        // "submit" through the *component* tree even across react-bootstrap
        // Modal's DOM portal, so without this an upload here also submits
        // that outer form.
        event.stopPropagation();
        const input = fileInputRef.current;
        if (!input?.files?.length) {
            return;
        }

        const formData = new FormData();
        formData.append('file', input.files[0]);

        setUploading(true);
        setError(null);
        try {
            const { data } = await client.post('/admin/files', formData);
            input.value = '';
            onUploaded(data);
        } catch {
            setError("Échec de l'envoi du fichier.");
        } finally {
            setUploading(false);
        }
    };

    return (
        <Form onSubmit={handleSubmit}>
            <div className="d-flex align-items-center gap-2">
                <Form.Control type="file" ref={fileInputRef} accept={accept} required style={{ maxWidth: '360px' }} />
                <Button type="submit" variant="primary" disabled={uploading}>
                    {uploading ? <Spinner animation="border" size="sm" /> : 'Envoyer'}
                </Button>
            </div>
            {error && (
                <div className="alert alert-danger mt-2 mb-0 py-1 px-2 small">{error}</div>
            )}
        </Form>
    );
}
