import React from 'react';
import { Card, Col, Row } from 'react-bootstrap';
import { useTranslator } from '../i18n/TranslationContext';

/**
 * Thumbnail grid shared by the full-page file manager (step 02) and the
 * media picker modal (step 03) - the two only differ in what action they
 * attach to each card, provided via `renderAction`.
 */
export default function FileGrid({ files, renderAction }) {
    const { t } = useTranslator();

    if (0 === files.length) {
        return <p>{t('fileGrid.empty')}</p>;
    }

    return (
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
                            {renderAction(file)}
                        </Card.Body>
                    </Card>
                </Col>
            ))}
        </Row>
    );
}
