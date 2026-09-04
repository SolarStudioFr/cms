import React, { useCallback, useEffect, useState } from 'react';
import { Button } from 'react-bootstrap';
import client from '../api/client';
import FileGrid from '../components/FileGrid';
import FileUploadForm from '../components/FileUploadForm';

/**
 * Admin file manager (step 02): browse every uploaded file, upload a new
 * one, delete one. Static admin page (file management is "pas un plugin"),
 * built directly on the FileController API from step 02. Shares its grid
 * and upload form with the media picker modal (step 03).
 */
export default function FileManager() {
    const [files, setFiles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

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

            <div className="mb-4">
                <FileUploadForm onUploaded={load} />
            </div>

            {error && <div className="alert alert-danger">{error}</div>}

            {loading ? (
                <p>Chargement...</p>
            ) : (
                <FileGrid
                    files={files}
                    renderAction={(file) => (
                        <Button size="sm" variant="outline-danger" onClick={() => remove(file.id)}>
                            Supprimer
                        </Button>
                    )}
                />
            )}
        </div>
    );
}
