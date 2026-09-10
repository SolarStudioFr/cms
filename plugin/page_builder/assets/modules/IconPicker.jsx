import React, { useMemo, useState } from 'react';
import { Button, Form, Modal } from 'react-bootstrap';
import { getIconComponent, iconNames } from './icons';
import useDomainTranslator from '../useDomainTranslator';

// Search is a plain substring match on the name with its "Bs" prefix
// stripped (e.g. "rocket" matches "BsRocketTakeoff") - capped so a broad
// query (or an empty one) doesn't render all ~100 candidates at once.
const MAX_RESULTS = 48;

/**
 * Modal icon search/picker (step 42, "Icône (avec recherche)"): a button
 * showing the current selection, opening a searchable grid over the
 * curated icon set (modules/icons.js). Reusable by any module needing an
 * icon field (services grid now; service-card/pricing later, steps 48-49).
 *
 * @param {string} value currently selected icon name, or ""
 * @param {(name: string) => void} onChange
 */
export default function IconPicker({ value, onChange }) {
    const { t } = useDomainTranslator('page_builder');
    const [show, setShow] = useState(false);
    const [query, setQuery] = useState('');

    const results = useMemo(() => {
        const needle = query.trim().toLowerCase();
        const names = needle
            ? iconNames().filter((name) => name.slice(2).toLowerCase().includes(needle))
            : iconNames();
        return names.slice(0, MAX_RESULTS);
    }, [query]);

    const SelectedIcon = value && getIconComponent(value);

    const pick = (name) => {
        onChange(name);
        setShow(false);
        setQuery('');
    };

    return (
        <>
            <Button size="sm" variant="outline-secondary" onClick={() => setShow(true)} className="d-inline-flex align-items-center gap-2">
                {SelectedIcon ? <SelectedIcon /> : null}
                {value || t('module.iconPicker.choose')}
            </Button>

            <Modal show={show} onHide={() => setShow(false)} centered>
                <Modal.Header closeButton>
                    <Modal.Title>{t('module.iconPicker.choose')}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form.Control
                        type="search"
                        placeholder={t('module.iconPicker.search')}
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        className="mb-3"
                        autoFocus
                    />
                    <div
                        className="d-flex flex-wrap gap-2"
                        style={{ maxHeight: '320px', overflowY: 'auto' }}
                    >
                        {results.map((name) => {
                            const Icon = getIconComponent(name);
                            return (
                                <Button
                                    key={name}
                                    size="sm"
                                    variant={name === value ? 'primary' : 'outline-secondary'}
                                    title={name}
                                    onClick={() => pick(name)}
                                >
                                    <Icon />
                                </Button>
                            );
                        })}
                        {0 === results.length && <p className="text-muted small mb-0">{t('module.iconPicker.noResults')}</p>}
                    </div>
                </Modal.Body>
            </Modal>
        </>
    );
}
