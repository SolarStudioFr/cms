import React, { useEffect, useRef, useState } from 'react';
import { Button, Dropdown } from 'react-bootstrap';
import staticNavItems from '../layout/staticNavItems';
import useAdminMenuOrder from '../layout/useAdminMenuOrder';
import { useTranslator } from '../i18n/TranslationContext';

/**
 * Admin sidebar order settings (step 32 add-on, not part of the original
 * backlog): lets a super-admin choose the display order of the admin
 * backend's own left nav (static pages + plugin-provided items) and insert
 * separators between them. Backed by AdminMenuConfig - see Sidebar.jsx for
 * how the saved order is applied (unconfigured/unknown items fall back to
 * their default position, so the sidebar never breaks before this is used).
 *
 * @param {Array<{key: string, label: string, path: string}>} pluginItems currently active plugins' nav items, from App.jsx
 */
export default function AdminMenuSettings({ pluginItems = [] }) {
    const { items: savedOrder, loading, save } = useAdminMenuOrder(true);
    const { t } = useTranslator();
    const [order, setOrder] = useState([]);
    const [saved, setSaved] = useState(false);
    const dragIndexRef = useRef(null);

    const knownItems = [...staticNavItems, ...pluginItems];
    const labelFor = (key) => t(knownItems.find((item) => item.key === key)?.label ?? key);

    useEffect(() => {
        if (!loading) {
            setOrder(savedOrder);
        }
        // Only sync from the server once, when the fetch resolves - after
        // that this is purely local editable state until Save is pressed.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [loading]);

    const placedKeys = new Set(order.filter((entry) => 'item' === entry.type).map((entry) => entry.key));
    const available = knownItems.filter((item) => !placedKeys.has(item.key));

    const addItem = (key) => setOrder([...order, { type: 'item', key }]);
    const addSeparator = () => setOrder([...order, { type: 'separator' }]);
    const removeAt = (index) => setOrder(order.filter((_, i) => i !== index));
    const moveEntry = (fromIndex, toIndex) => {
        const next = [...order];
        const [moved] = next.splice(fromIndex, 1);
        next.splice(toIndex, 0, moved);
        setOrder(next);
    };

    const handleSave = async () => {
        await save(order);
        setSaved(true);
        setTimeout(() => setSaved(false), 2000);
    };

    if (loading) {
        return <p>{t('common.loading')}</p>;
    }

    return (
        <div>
            <h1 className="mb-4">{t('adminMenuSettings.title')}</h1>
            <p className="text-muted">{t('adminMenuSettings.description')}</p>

            {saved && <div className="alert alert-success">{t('adminMenuSettings.saved')}</div>}

            <div className="d-flex gap-2 mb-2">
                <Dropdown>
                    <Dropdown.Toggle variant="outline-primary" size="sm" disabled={0 === available.length}>
                        {t('adminMenuSettings.addItem')}
                    </Dropdown.Toggle>
                    <Dropdown.Menu>
                        {available.map((item) => (
                            <Dropdown.Item key={item.key} onClick={() => addItem(item.key)}>
                                {t(item.label)}
                            </Dropdown.Item>
                        ))}
                    </Dropdown.Menu>
                </Dropdown>
                <Button variant="outline-secondary" size="sm" onClick={addSeparator}>
                    {t('adminMenuSettings.addSeparator')}
                </Button>
            </div>

            {0 === order.length && (
                <p className="text-muted border rounded p-3 text-center">{t('adminMenuSettings.empty')}</p>
            )}

            {order.map((entry, index) => (
                <div
                    key={index}
                    draggable
                    onDragStart={() => {
                        dragIndexRef.current = index;
                    }}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={() => {
                        if (null !== dragIndexRef.current && dragIndexRef.current !== index) {
                            moveEntry(dragIndexRef.current, index);
                        }
                        dragIndexRef.current = null;
                    }}
                    className="border rounded p-2 mb-2 bg-white d-flex align-items-center gap-2"
                >
                    <strong className="text-muted" style={{ cursor: 'move' }}>
                        ⠿
                    </strong>
                    <div className="flex-grow-1">
                        {'separator' === entry.type ? (
                            <span className="text-muted fst-italic">{t('adminMenuSettings.separator')}</span>
                        ) : (
                            labelFor(entry.key)
                        )}
                    </div>
                    <Button size="sm" variant="outline-danger" onClick={() => removeAt(index)}>
                        {t('adminMenuSettings.remove')}
                    </Button>
                </div>
            ))}

            <Button variant="primary" onClick={handleSave} className="mt-2">
                {t('common.save')}
            </Button>
        </div>
    );
}
