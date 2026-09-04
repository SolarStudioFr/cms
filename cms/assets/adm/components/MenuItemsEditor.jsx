import React, { useRef } from 'react';
import { Button, Form } from 'react-bootstrap';

/**
 * Ordered editor for a Menu's `items` array (step 32): add a link or a
 * separator, edit a link's label/url/target inline, remove, reorder by
 * native HTML5 drag & drop - same technique as the page builder's
 * BuilderCanvas (plugin/page_builder/assets/BuilderCanvas.jsx), duplicated
 * here rather than shared cross-remote since Menus aren't a plugin and
 * don't consume Module Federation.
 *
 * @param {Array<{id: string, type: 'link'|'separator', label?: string, url?: string, target?: string}>} items
 * @param {(items: Array) => void} onChange
 */
export default function MenuItemsEditor({ items, onChange }) {
    const dragIndexRef = useRef(null);

    const addLink = () => {
        onChange([...items, { id: crypto.randomUUID(), type: 'link', label: '', url: '', target: '_self' }]);
    };

    const addSeparator = () => {
        onChange([...items, { id: crypto.randomUUID(), type: 'separator' }]);
    };

    const updateItem = (id, changes) => {
        onChange(items.map((item) => (item.id === id ? { ...item, ...changes } : item)));
    };

    const removeItem = (id) => {
        onChange(items.filter((item) => item.id !== id));
    };

    const moveItem = (fromIndex, toIndex) => {
        const next = [...items];
        const [moved] = next.splice(fromIndex, 1);
        next.splice(toIndex, 0, moved);
        onChange(next);
    };

    return (
        <div>
            <div className="d-flex gap-2 mb-2">
                <Button variant="outline-primary" size="sm" onClick={addLink}>
                    Ajouter un lien
                </Button>
                <Button variant="outline-secondary" size="sm" onClick={addSeparator}>
                    Ajouter un séparateur
                </Button>
            </div>

            {0 === items.length && (
                <p className="text-muted border rounded p-3 text-center">Aucun élément dans ce menu.</p>
            )}

            {items.map((item, index) => (
                <div
                    key={item.id}
                    draggable
                    onDragStart={() => {
                        dragIndexRef.current = index;
                    }}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={() => {
                        if (null !== dragIndexRef.current && dragIndexRef.current !== index) {
                            moveItem(dragIndexRef.current, index);
                        }
                        dragIndexRef.current = null;
                    }}
                    className="border rounded p-2 mb-2 bg-white d-flex align-items-start gap-2"
                >
                    <strong className="text-muted" style={{ cursor: 'move' }}>
                        ⠿
                    </strong>

                    {'separator' === item.type ? (
                        <div className="flex-grow-1 text-muted small fst-italic py-1">— Séparateur —</div>
                    ) : (
                        <div className="flex-grow-1 d-flex gap-2 flex-wrap">
                            <Form.Control
                                size="sm"
                                placeholder="Libellé"
                                value={item.label ?? ''}
                                onChange={(e) => updateItem(item.id, { label: e.target.value })}
                                style={{ maxWidth: '180px' }}
                            />
                            <Form.Control
                                size="sm"
                                placeholder="URL (ex: /pages, https://...)"
                                value={item.url ?? ''}
                                onChange={(e) => updateItem(item.id, { url: e.target.value })}
                                style={{ maxWidth: '260px' }}
                            />
                            <Form.Select
                                size="sm"
                                value={item.target ?? '_self'}
                                onChange={(e) => updateItem(item.id, { target: e.target.value })}
                                style={{ maxWidth: '160px' }}
                            >
                                <option value="_self">Même onglet</option>
                                <option value="_blank">Nouvel onglet</option>
                            </Form.Select>
                        </div>
                    )}

                    <Button size="sm" variant="outline-danger" onClick={() => removeItem(item.id)}>
                        Supprimer
                    </Button>
                </div>
            ))}
        </div>
    );
}
