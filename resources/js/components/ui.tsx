import type { ReactNode } from 'react';
import { ApiError } from '@/api/client';
import type { PurchaseOrderStatus } from '@/api/types';

export function Card({ title, actions, children }: { title?: ReactNode; actions?: ReactNode; children: ReactNode }) {
    return (
        <section className="card">
            {(title || actions) && (
                <header className="mb-3 flex items-center justify-between gap-3">
                    {title && <h2 className="text-sm font-semibold text-gray-800">{title}</h2>}
                    {actions}
                </header>
            )}
            {children}
        </section>
    );
}

export function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <label className="block">
            <span className="label">{label}</span>
            {children}
            {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
        </label>
    );
}

/** Renders an ApiError (validation list or domain message) or any other error. */
export function ErrorBanner({ error }: { error: unknown }) {
    if (!error) return null;

    const messages = error instanceof ApiError ? error.allMessages() : [(error as Error).message ?? 'Something went wrong'];

    return (
        <div role="alert" className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            {messages.length === 1 ? (
                messages[0]
            ) : (
                <ul className="list-disc pl-4">
                    {messages.map((m, i) => (
                        <li key={i}>{m}</li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export function Empty({ children }: { children: ReactNode }) {
    return <p className="py-6 text-center text-sm text-gray-500">{children}</p>;
}

const statusStyles: Record<PurchaseOrderStatus, string> = {
    draft: 'bg-gray-100 text-gray-700',
    sent: 'bg-blue-100 text-blue-800',
    received: 'bg-amber-100 text-amber-800',
    closed: 'bg-green-100 text-green-800',
};

export function StatusBadge({ status, label }: { status: PurchaseOrderStatus; label: string }) {
    return <span className={`badge ${statusStyles[status]}`}>{label}</span>;
}
