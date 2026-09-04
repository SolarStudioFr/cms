import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Card, Col, Form, Row, Spinner } from 'react-bootstrap';
import client from '../api/client';

/**
 * Admin file manager (step 02): browse every uploaded file, upload a new
 * one, delete one. Static admin page (file management is "pas un plugin"),
 * built directly on the FileController API from step 02. The grid/upload
 * logic here is reused as the base for the media picker modal (step 03).
 */
export default function FileManager() {
    const [files, setFiles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState(null);
    const fileInputRef = useRef(null);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/files')
            .then(({ data }) => setFiles(data))
            .catch(() => setError('Impossible de charger les fichiers.'))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const handleUpload = async (event) => {
        event.preventDefault();
        const input = fileInputRef.current;
        if (!input?.files?.length) {
            return;
        }

        const formData = new FormData();
        formData.append('file', input.files[0]);

        setUploading(true);
        setError(null);
        try {
            await client.post('/admin/files', formData);
            input.value = '';
            load();
        } catch {
            setError("Échec de l'envoi du fichier.");
        } finally {
            setUploading(false);
        }
    };

    const remove = async (id) => {
        if (!window.confirm('Supprimer ce fichier ?')) {
            return;
        }
        await client.delete(`/admin/files/${id}`);
        load();
    };

    return (
        <div>
            <h1 className="mb-4">Fichiers</h1>

            <Form onSubmit={handleUpload} className="d-flex align-items-center gap-2 mb-4">
                <Form.Control type="file" ref={fileInputRef} required style={{ maxWidth: '360px' }} />
                <Button type="submit" variant="primary" disabled={uploading}>
                    {uploading ? <Spinner animation="border" size="sm" /> : 'Envoyer'}
                </Button>
            </Form>

            {error && <div className="alert alert-danger">{error}</div>}

            {loading ? (
                <p>Chargement...</p>
            ) : files.length === 0 ? (
                <p>Aucun fichier.</p>
            ) : (
                <Row xs={2} md={4} lg={6} className="g-3">
                    {files.map((file) => (
                        <Col key={file.id}>
                            <Card>
                                {'img' === file.type ? (
                                    <Card.Img
                                        variant="top"
                                        src={file.thumbnails[0] ?? file.url}
                                        alt={file.name}
                                        style={{ height: '120px', objectFit: 'cover' }}
                                    />
                                ) : (
                                    <div
                                        className="d-flex align-items-center justify-content-center bg-light text-uppercase text-muted"
                                        style={{ height: '120px' }}
                                    >
                                        {file.type}
                                    </div>
                                )}
                                <Card.Body className="p-2">
                                    <Card.Text className="text-truncate small mb-2" title={file.name}>
                                        {file.name}
                                    </Card.Text>
                                    <Button size="sm" variant="outline-danger" onClick={() => remove(file.id)}>
                                        Supprimer
                                    </Button>
                                </Card.Body>
                            </Card>
                        </Col>
                    ))}
                </Row>
            )}
        </div>
    );
}
