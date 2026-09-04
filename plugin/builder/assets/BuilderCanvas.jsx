import React, { useRef, useState } from 'react';
import { Button, Dropdown } from 'react-bootstrap';
import registry, { getModule } from './modules/registry';

/**
 * Drag & drop page builder canvas (step 10): an ordered list of module
 * blocks, add/remove/reorder, each block edited inline via its registered
 * module's `Edit` component (see modules/registry.js).
 *
 * Drop-in replacement for RichTextEditor (step 09) from a consuming
 * plugin's point of view: same value/onChange(string) contract. The value
 * is a JSON string `{"builder": true, "modules": [{id, type, props}]}`;
 * a consuming plugin renders it publicly via the sibling `renderToHtml.js`.
 *
 * @param {string} value initial JSON value (see above); parsed once on mount
 * @param {(json: string) => void} onChange called with the updated JSON on every edit
 */
export default function BuilderCanvas({ value, onChange }) {
    const [modules, setModules] = useState(() => {
        try {
            return value ? (JSON.parse(value).modules ?? []) : [];
        } catch {
            return [];
        }
    });
    const dragIndexRef = useRef(null);

    const commit = (next) => {
        setModules(next);
        onChange(JSON.stringify({ builder: true, modules: next }));
    };

    const addModule = (type) => {
        const definition = getModule(type);
        if (!definition) {
            return;
        }
        commit([...modules, { id: crypto.randomUUID(), type, props: { ...definition.defaultProps } }]);
    };

    const updateModule = (id, props) => {
        commit(modules.map((module) => (module.id === id ? { ...module, props } : module)));
    };

    const removeModule = (id) => {
        commit(modules.filter((module) => module.id !== id));
    };

    const moveModule = (fromIndex, toIndex) => {
        const next = [...modules];
        const [moved] = next.splice(fromIndex, 1);
        next.splice(toIndex, 0, moved);
        commit(next);
    };

    return (
        <div>
            <div className="d-flex justify-content-end mb-2">
                <Dropdown>
                    <Dropdown.Toggle variant="outline-primary" size="sm" disabled={0 === registry.length}>
                        Ajouter un module
                    </Dropdown.Toggle>
                    <Dropdown.Menu>
                        {registry.map((module) => (
                            <Dropdown.Item key={module.type} onClick={() => addModule(module.type)}>
                                {module.label}
                            </Dropdown.Item>
                        ))}
                    </Dropdown.Menu>
                </Dropdown>
            </div>

            {0 === modules.length && (
                <p className="text-muted border rounded p-3 text-center">
                    Aucun module. Utilisez « Ajouter un module » pour commencer.
                </p>
            )}

            {modules.map((block, index) => {
                const definition = getModule(block.type);
                const EditComponent = definition?.Edit;

                return (
                    <div
                        key={block.id}
                        draggable
                        onDragStart={() => {
                            dragIndexRef.current = index;
                        }}
                        onDragOver={(event) => event.preventDefault()}
                        onDrop={() => {
                            if (null !== dragIndexRef.current && dragIndexRef.current !== index) {
                                moveModule(dragIndexRef.current, index);
                            }
                            dragIndexRef.current = null;
                        }}
                        className="border rounded p-2 mb-2 bg-white"
                        data-testid="builder-block"
                        data-block-type={block.type}
                    >
                        <div className="d-flex justify-content-between align-items-center mb-2">
                            <strong className="small text-uppercase text-muted" style={{ cursor: 'move' }}>
                                ⠿ {definition?.label ?? block.type}
                            </strong>
                            <Button size="sm" variant="outline-danger" onClick={() => removeModule(block.id)}>
                                Supprimer
                            </Button>
                        </div>
                        {EditComponent ? (
                            <EditComponent props={block.props} onChange={(props) => updateModule(block.id, props)} />
                        ) : (
                            <p className="text-danger small mb-0">Module inconnu : {block.type}</p>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
