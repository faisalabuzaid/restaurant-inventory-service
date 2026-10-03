/**
 * Random id for POS idempotency keys. `crypto.randomUUID` only exists in
 * secure contexts (https or localhost), so opening the app via a LAN IP or
 * a Docker hostname needs a fallback. Uniqueness, not cryptographic strength,
 * is all the key needs.
 */
export function randomId(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}
