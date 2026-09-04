import React, { useCallback, useEffect, useState } from 'react';
import { Button } from 'react-bootstrap';
import client from '../api/client';
import FileGrid from '../components/FileGrid';
import FileUploadForm from '../components/FileUploadForm';

/**
 * Admin file manager (step 02): browse every uploaded file, upload a new
 * one, delete one. Also exposes the "delete unused files" (step 04) and
 * "re-optimize all images" (step 05) admin actions. Static admin page (file
 * management is "pas un plugin"), built directly on the FileController API.
 * Its grid/upload form are shared with the media picker modal (step 03).
 */
export default function FileManager() {
    const [files, setFiles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [notice, setNotice] = useState(null);
    const [cleaning, setCleaning] = useState(false);
    const [reoptimizing, setReoptimizing] = useState(false);

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

    const cleanupUnused = async () => {
        if (!window.confirm('Supprimer tous les fichiers non utilisés ?')) {
            return;
        }
        setCleaning(true);
        setNotice(null);
        try {
            const { data } = await client.post('/admin/files/cleanup-unused');
            setNotice(`${data.deleted} fichier(s) supprimé(s).`);
            load();
        } finally {
            setCleaning(false);
        }
    };

    const reoptimizeImages = async () => {
        setReoptimizing(true);
        setNotice(null);
        try {
            const { data } = await client.post('/admin/files/reoptimize-images');
            setNotice(`${data.reoptimized} image(s) ré-optimisée(s), ${data.skipped} ignorée(s) (source manquante).`);
            load();
        } finally {
            setReoptimizing(false);
        }
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h1>Fichiers</h1>
                <div className="d-flex gap-2">
                    <Button variant="outline-secondary" size="sm" onClick={reoptimizeImages} disabled={reoptimizing}>
                        Ré-optimiser toutes les images
                    </Button>
                    <Button variant="outline-warning" size="sm" onClick={cleanupUnused} disabled={cleaning}>
                        Supprimer les fichiers non utilisés
                    </Button>
                </div>
            </div>

            <div className="mb-4">
                <FileUploadForm onUploaded={load} />
            </div>

            {notice && <div className="alert alert-info">{notice}</div>}
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
