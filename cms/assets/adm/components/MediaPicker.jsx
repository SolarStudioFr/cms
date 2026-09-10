import React, { useCallback, useEffect, useState } from 'react';
import { Button, Modal } from 'react-bootstrap';
import client from '../api/client';
import FileGrid from './FileGrid';
import FileUploadForm from './FileUploadForm';
import { useTranslator } from '../i18n/TranslationContext';

/**
 * Reusable file/image selection modal (step 03), built on top of the
 * step-01/02 file backend. Any content editor (builder modules, page,
 * actualités, réalisations, accueil...) opens this to either pick an
 * already-uploaded file or upload a new one, restricted to the file types
 * it passes in `types` (e.g. ["img"], or ["pdf", "zip"] for a "download a
 * file" module) - never through an ad hoc upload field of its own.
 *
 * Controlled component: the caller owns `show`/`onHide`, as is standard
 * for react-bootstrap's Modal.
 *
 * @param {boolean} show
 * @param {() => void} onHide
 * @param {(file: object) => void} onSelect called with the chosen/uploaded File, right before the modal closes
 * @param {string[]} [types] File types to show/accept, e.g. ["img"]. Omit to allow every type.
 * @param {string} [title] Defaults to the translated "Choose a file" when omitted.
 */
export default function MediaPicker({ show, onHide, onSelect, types, title }) {
    const { t } = useTranslator();
    const [files, setFiles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/files', { params: types?.length ? { type: types.join(',') } : undefined })
            .then(({ data }) => setFiles(data))
            .catch(() => setError(t('files.loadError')))
            .finally(() => setLoading(false));
    }, [types, t]);

    useEffect(() => {
        if (show) {
            load();
        }
    }, [show, load]);

    const choose = (file) => {
        onSelect(file);
        onHide();
    };

    return (
        <Modal show={show} onHide={onHide} size="lg" centered>
            <Modal.Header closeButton>
                <Modal.Title>{title ?? t('mediaPicker.chooseFile')}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="mb-3">
                    <FileUploadForm types={types} onUploaded={choose} />
                </div>

                {error && <div className="alert alert-danger">{error}</div>}

                {loading ? (
                    <p>{t('common.loading')}</p>
                ) : (
                    <FileGrid
                        files={files}
                        renderAction={(file) => (
                            <Button size="sm" variant="outline-primary" onClick={() => choose(file)}>
                                {t('mediaPicker.choose')}
                            </Button>
                        )}
                    />
                )}
            </Modal.Body>
        </Modal>
    );
}
