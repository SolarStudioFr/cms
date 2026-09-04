/**
 * Applies a saved AdminMenuConfig order on top of the full set of known nav
 * items (static + plugin-provided). Any known item missing from `savedOrder`
 * (never configured yet, or a plugin installed after the order was last
 * saved) is appended at the end in its default position - the sidebar must
 * never silently drop an item just because it wasn't explicitly placed.
 * A `savedOrder` key that no longer matches a known item (e.g. a deleted
 * plugin) is dropped.
 *
 * @param {Array<{key: string, label: string, path: string}>} knownItems
 * @param {Array<{type: 'item', key: string} | {type: 'separator'}>} savedOrder
 * @returns {Array<{kind: 'item', key: string, label: string, path: string} | {kind: 'separator', id: string}>}
 */
export default function resolveNavOrder(knownItems, savedOrder) {
    const byKey = new Map(knownItems.map((item) => [item.key, item]));
    const placedKeys = new Set();
    const result = [];

    savedOrder.forEach((entry, index) => {
        if ('separator' === entry.type) {
            result.push({ kind: 'separator', id: `sep-${index}` });
            return;
        }
        const item = byKey.get(entry.key);
        if (item) {
            result.push({ kind: 'item', ...item });
            placedKeys.add(entry.key);
        }
    });

    knownItems
        .filter((item) => !placedKeys.has(item.key))
        .forEach((item) => result.push({ kind: 'item', ...item }));

    return result;
}
