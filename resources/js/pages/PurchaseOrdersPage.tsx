import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, ApiError, keys } from '@/api/client';
import type { Ingredient, PurchaseOrder, Supplier } from '@/api/types';
import { emptyLine, QuantityLinesEditor, toPayload, type LineDraft } from '@/components/QuantityLinesEditor';
import { Card, Empty, ErrorBanner, Field, StatusBadge } from '@/components/ui';
import { dateTime, qty } from '@/lib/format';

/** Everything that can change stock or order status invalidates these. */
function useInvalidateOrders() {
    const queryClient = useQueryClient();
    return () => {
        queryClient.invalidateQueries({ queryKey: keys.purchaseOrders });
        queryClient.invalidateQueries({ queryKey: keys.stock });
    };
}

export function PurchaseOrdersPage() {
    const [onlyOpen, setOnlyOpen] = useState(true);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [creating, setCreating] = useState(false);

    const orders = useQuery({
        queryKey: [...keys.purchaseOrders, { onlyOpen }],
        queryFn: () => api.purchaseOrders.list(onlyOpen),
    });
    const suppliers = useQuery({ queryKey: keys.suppliers, queryFn: api.suppliers.list });
    const ingredients = useQuery({ queryKey: keys.ingredients, queryFn: api.ingredients.list });

    const selected = useMemo(() => orders.data?.find((o) => o.id === selectedId) ?? null, [orders.data, selectedId]);

    // Keep a sensible selection as the list changes (e.g. an order closes and leaves the open list).
    useEffect(() => {
        if (orders.data && !orders.data.some((o) => o.id === selectedId)) {
            setSelectedId(orders.data[0]?.id ?? null);
        }
    }, [orders.data, selectedId]);

    return (
        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
            <div className="space-y-6">
                <Card
                    title="Purchase orders"
                    actions={
                        <div className="flex items-center gap-3">
                            <label className="flex items-center gap-1.5 text-xs text-gray-600">
                                <input type="checkbox" checked={onlyOpen} onChange={(e) => setOnlyOpen(e.target.checked)} />
                                Open only
                            </label>
                            <button type="button" className="btn btn-primary" onClick={() => setCreating(true)}>
                                New order
                            </button>
                        </div>
                    }
                >
                    {orders.isLoading ? (
                        <Empty>Loading...</Empty>
                    ) : orders.data?.length ? (
                        <ul className="divide-y divide-gray-100">
                            {orders.data.map((o) => (
                                <li key={o.id}>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelectedId(o.id);
                                            setCreating(false);
                                        }}
                                        className={`flex w-full items-start justify-between gap-3 rounded-md px-2 py-2.5 text-left hover:bg-gray-50 ${
                                            o.id === selectedId && !creating ? 'bg-gray-100' : ''
                                        }`}
                                    >
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium">
                                                #{o.id} {o.supplier_name}
                                            </p>
                                            <p className="truncate text-xs text-gray-500">
                                                {o.lines
                                                    .map((l) =>
                                                        o.is_open && o.status !== 'draft'
                                                            ? `${l.ingredient_name}: ${qty(l.outstanding, l.unit)} outstanding`
                                                            : `${l.ingredient_name}: ${qty(l.quantity_ordered, l.unit)}`,
                                                    )
                                                    .join(' · ')}
                                            </p>
                                        </div>
                                        <StatusBadge status={o.status} label={o.status_label} />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <Empty>{onlyOpen ? 'No open purchase orders.' : 'No purchase orders yet.'}</Empty>
                    )}
                </Card>
            </div>

            <div className="space-y-6">
                {creating ? (
                    <CreateOrderForm
                        suppliers={suppliers.data ?? []}
                        ingredients={ingredients.data ?? []}
                        onCreated={(order) => {
                            setCreating(false);
                            setOnlyOpen(true);
                            setSelectedId(order.id);
                        }}
                        onCancel={() => setCreating(false)}
                    />
                ) : selected ? (
                    <OrderDetail key={selected.id} order={selected} />
                ) : (
                    <Card>
                        <Empty>Select an order or create a new one.</Empty>
                    </Card>
                )}
            </div>
        </div>
    );
}

function CreateOrderForm({
    suppliers,
    ingredients,
    onCreated,
    onCancel,
}: {
    suppliers: Supplier[];
    ingredients: Ingredient[];
    onCreated: (order: PurchaseOrder) => void;
    onCancel: () => void;
}) {
    const invalidate = useInvalidateOrders();
    const [supplierId, setSupplierId] = useState('');
    const [notes, setNotes] = useState('');
    const [lines, setLines] = useState<LineDraft[]>([emptyLine()]);

    const create = useMutation({
        mutationFn: api.purchaseOrders.create,
        onSuccess: (order) => {
            invalidate();
            onCreated(order);
        },
    });
    const error = create.error instanceof ApiError ? create.error : null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        create.mutate({ supplier_id: Number(supplierId), notes: notes.trim() || undefined, lines: toPayload(lines) });
    };

    return (
        <Card
            title="New purchase order"
            actions={
                <button type="button" className="text-sm text-gray-500 hover:text-gray-900" onClick={onCancel}>
                    Cancel
                </button>
            }
        >
            <form onSubmit={submit} className="space-y-3">
                <Field label="Supplier" error={error?.field('supplier_id')}>
                    <select className="input" value={supplierId} onChange={(e) => setSupplierId(e.target.value)} required>
                        <option value="">Select supplier</option>
                        {suppliers.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.name}
                            </option>
                        ))}
                    </select>
                </Field>
                <div>
                    <span className="label">Lines</span>
                    <QuantityLinesEditor ingredients={ingredients} lines={lines} onChange={setLines} error={error} />
                </div>
                <Field label="Notes (optional)" error={error?.field('notes')}>
                    <input className="input" value={notes} onChange={(e) => setNotes(e.target.value)} />
                </Field>
                {error && !Object.keys(error.errors).length && <ErrorBanner error={error} />}
                <p className="text-xs text-gray-500">The order is created as a draft; send it to the supplier from its detail view.</p>
                <button type="submit" className="btn btn-primary" disabled={create.isPending}>
                    Create draft
                </button>
            </form>
        </Card>
    );
}

function OrderDetail({ order }: { order: PurchaseOrder }) {
    const invalidate = useInvalidateOrders();

    const send = useMutation({
        mutationFn: () => api.purchaseOrders.send(order.id),
        onSuccess: invalidate,
    });

    return (
        <>
            <Card
                title={
                    <span className="flex items-center gap-2">
                        Order #{order.id} · {order.supplier_name}
                        <StatusBadge status={order.status} label={order.status_label} />
                    </span>
                }
                actions={
                    order.status === 'draft' ? (
                        <button type="button" className="btn btn-primary" onClick={() => send.mutate()} disabled={send.isPending}>
                            Send to supplier
                        </button>
                    ) : null
                }
            >
                {send.error ? <div className="mb-3"><ErrorBanner error={send.error} /></div> : null}
                <dl className="mb-3 grid grid-cols-3 gap-2 text-xs text-gray-600">
                    <div>
                        <dt className="font-medium text-gray-500">Created</dt>
                        <dd>{dateTime(order.created_at)}</dd>
                    </div>
                    <div>
                        <dt className="font-medium text-gray-500">Sent</dt>
                        <dd>{dateTime(order.sent_at)}</dd>
                    </div>
                    <div>
                        <dt className="font-medium text-gray-500">Closed</dt>
                        <dd>{dateTime(order.closed_at)}</dd>
                    </div>
                </dl>
                {order.notes && <p className="mb-3 text-sm text-gray-700">{order.notes}</p>}

                <table className="table">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th className="text-right">Ordered</th>
                            <th className="text-right">Received</th>
                            <th className="text-right">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        {order.lines.map((l) => (
                            <tr key={l.id}>
                                <td>{l.ingredient_name}</td>
                                <td className="text-right">{qty(l.quantity_ordered, l.unit)}</td>
                                <td className="text-right">{qty(l.quantity_received, l.unit)}</td>
                                <td className={`text-right ${l.outstanding > 0 ? 'font-medium text-amber-700' : 'text-gray-400'}`}>
                                    {qty(l.outstanding, l.unit)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>

            {order.accepts_deliveries && <RecordDeliveryForm order={order} />}

            <Card title={`Deliveries (${order.deliveries.length})`}>
                {order.deliveries.length ? (
                    <ul className="space-y-3">
                        {order.deliveries.map((d) => (
                            <li key={d.id} className="text-sm">
                                <p className="font-medium">
                                    {dateTime(d.received_at)}
                                    {d.note && <span className="ml-2 font-normal text-gray-500">{d.note}</span>}
                                </p>
                                <p className="text-gray-600">
                                    {d.lines.map((l) => `${qty(l.quantity, l.unit)} ${l.ingredient_name}`).join(', ')}
                                </p>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <Empty>Nothing received yet.</Empty>
                )}
            </Card>
        </>
    );
}

function RecordDeliveryForm({ order }: { order: PurchaseOrder }) {
    const invalidate = useInvalidateOrders();
    const openLines = order.lines.filter((l) => l.outstanding > 0);

    // Prefill with the outstanding quantity; the manager edits down what did not arrive.
    const [quantities, setQuantities] = useState<Record<number, string>>(() =>
        Object.fromEntries(openLines.map((l) => [l.id, String(l.outstanding)])),
    );
    const [note, setNote] = useState('');

    const record = useMutation({
        mutationFn: () =>
            api.purchaseOrders.recordDelivery(order.id, {
                note: note.trim() || undefined,
                lines: openLines
                    .filter((l) => Number(quantities[l.id]) > 0)
                    .map((l) => ({ purchase_order_line_id: l.id, quantity: Number(quantities[l.id]) })),
            }),
        onSuccess: () => {
            setNote('');
            invalidate();
        },
    });
    const error = record.error instanceof ApiError ? record.error : null;

    // Server-side indexes refer to the filtered payload.
    const payloadIndex = (lineId: number) =>
        openLines.filter((l) => Number(quantities[l.id]) > 0).findIndex((l) => l.id === lineId);

    const overReceiptLineId = error?.code === 'over_receipt' ? (error.body.purchase_order_line_id as number) : null;

    return (
        <Card title="Record delivery">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    record.mutate();
                }}
                className="space-y-3"
            >
                <table className="table">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th className="text-right">Outstanding</th>
                            <th className="w-40">Arrived</th>
                        </tr>
                    </thead>
                    <tbody>
                        {openLines.map((l) => {
                            const fieldError =
                                error?.field(`lines.${payloadIndex(l.id)}.quantity`) ??
                                (overReceiptLineId === l.id ? error?.message : undefined);
                            return (
                                <tr key={l.id}>
                                    <td>{l.ingredient_name}</td>
                                    <td className="text-right">{qty(l.outstanding, l.unit)}</td>
                                    <td>
                                        <div className="flex items-center gap-2">
                                            <input
                                                className="input"
                                                type="number"
                                                min="0"
                                                step="0.001"
                                                value={quantities[l.id] ?? ''}
                                                onChange={(e) => setQuantities({ ...quantities, [l.id]: e.target.value })}
                                                aria-label={`Arrived ${l.ingredient_name}`}
                                            />
                                            <span className="text-xs text-gray-500">{l.unit}</span>
                                        </div>
                                        {fieldError && <p className="mt-1 text-xs text-red-600">{fieldError}</p>}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
                <Field label="Note (optional)" error={error?.field('note')}>
                    <input className="input" value={note} onChange={(e) => setNote(e.target.value)} placeholder="e.g. 2 crates short" />
                </Field>
                {error && !Object.keys(error.errors).length && error.code !== 'over_receipt' && <ErrorBanner error={error} />}
                {error?.field('lines') && <ErrorBanner error={error} />}
                <p className="text-xs text-gray-500">
                    Set a line to 0 if nothing of it arrived. Stock increases by exactly what you enter; the order closes
                    automatically once every line is fully received.
                </p>
                <button type="submit" className="btn btn-primary" disabled={record.isPending}>
                    Record delivery
                </button>
            </form>
        </Card>
    );
}
