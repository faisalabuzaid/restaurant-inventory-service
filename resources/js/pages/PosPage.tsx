import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, ApiError, keys } from '@/api/client';
import type { SaleResponse } from '@/api/types';
import { Card, Empty, ErrorBanner, Field } from '@/components/ui';
import { dateTime, qty } from '@/lib/format';

/**
 * Simulates the POS calling POST /api/sales. Each form fill gets a fresh
 * idempotency key, so double-clicking "Record sale" replays instead of
 * selling twice, the same way a real POS retry would.
 */
export function PosPage() {
    const queryClient = useQueryClient();
    const menuItems = useQuery({ queryKey: keys.menuItems, queryFn: api.menuItems.list });
    const sales = useQuery({ queryKey: keys.sales, queryFn: api.sales.list });

    const [menuItemId, setMenuItemId] = useState('');
    const [quantity, setQuantity] = useState('1');
    const [key, setKey] = useState(() => crypto.randomUUID());
    const [last, setLast] = useState<SaleResponse | null>(null);

    const record = useMutation({
        mutationFn: api.sales.record,
        onSuccess: (result) => {
            setLast(result);
            setKey(crypto.randomUUID());
            queryClient.invalidateQueries({ queryKey: keys.stock });
            queryClient.invalidateQueries({ queryKey: keys.sales });
        },
    });
    const error = record.error instanceof ApiError ? record.error : null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        record.mutate({ menu_item_id: Number(menuItemId), quantity: Number(quantity), idempotency_key: key });
    };

    const selectedItem = menuItems.data?.find((m) => String(m.id) === menuItemId);

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <div className="space-y-6">
                <Card title="Record a sale (as the POS would)">
                    <form onSubmit={submit} className="space-y-3">
                        <Field label="Menu item" error={error?.field('menu_item_id')}>
                            <select className="input" value={menuItemId} onChange={(e) => setMenuItemId(e.target.value)} required>
                                <option value="">Select menu item</option>
                                {menuItems.data?.map((m) => (
                                    <option key={m.id} value={m.id}>
                                        {m.name}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        {selectedItem && (
                            <p className="text-xs text-gray-500">
                                Each one uses {selectedItem.recipe.map((r) => `${qty(r.quantity, r.unit)} ${r.ingredient_name}`).join(', ')}
                            </p>
                        )}
                        <Field label="Quantity" error={error?.field('quantity')}>
                            <input className="input" type="number" min="1" step="1" value={quantity} onChange={(e) => setQuantity(e.target.value)} required />
                        </Field>
                        {error && !Object.keys(error.errors).length && <ErrorBanner error={error} />}
                        <button type="submit" className="btn btn-primary" disabled={record.isPending}>
                            Record sale
                        </button>
                    </form>
                </Card>

                {last && (
                    <Card title={last.replayed ? 'Duplicate ignored' : 'Sale recorded'}>
                        {last.replayed ? (
                            <p className="text-sm text-gray-700">
                                This sale (#{last.data.id}) had already been recorded with the same idempotency key; stock was
                                not changed again.
                            </p>
                        ) : (
                            <>
                                <p className="mb-2 text-sm text-gray-700">
                                    {last.data.quantity} x {last.data.menu_item_name}
                                </p>
                                <table className="table">
                                    <thead>
                                        <tr>
                                            <th>Ingredient</th>
                                            <th className="text-right">Used</th>
                                            <th className="text-right">Stock now</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {last.consumed.map((c) => (
                                            <tr key={c.ingredient_id} className={c.stock_after < 0 ? 'bg-red-50' : ''}>
                                                <td>{c.ingredient_name}</td>
                                                <td className="text-right tabular-nums">-{qty(c.quantity, c.unit)}</td>
                                                <td className={`text-right tabular-nums ${c.stock_after < 0 ? 'font-semibold text-red-700' : ''}`}>
                                                    {qty(c.stock_after, c.unit)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {last.warnings.length > 0 && (
                                    <div role="alert" className="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                                        The sale was recorded, but stock is now negative for{' '}
                                        {last.warnings.map((w) => w.ingredient_name).join(', ')}. Check the storeroom and the
                                        recipe.
                                    </div>
                                )}
                            </>
                        )}
                    </Card>
                )}

                <Card title="From a real POS">
                    <pre className="overflow-x-auto rounded-md bg-gray-900 p-3 text-xs text-gray-100">
                        {`curl -X POST http://localhost:8000/api/sales \\
  -H 'Content-Type: application/json' \\
  -d '{"menu_item_id": ${menuItemId || 1}, "quantity": ${quantity || 1}, "idempotency_key": "ticket-1234"}'`}
                    </pre>
                    <p className="mt-2 text-xs text-gray-500">The Stock tab picks the change up within a few seconds.</p>
                </Card>
            </div>

            <Card title="Recent sales">
                {sales.isLoading ? (
                    <Empty>Loading...</Empty>
                ) : sales.data?.length ? (
                    <ul className="divide-y divide-gray-100">
                        {sales.data.map((s) => (
                            <li key={s.id} className="py-2.5">
                                <div className="flex items-center justify-between">
                                    <p className="text-sm font-medium">
                                        {s.quantity} x {s.menu_item_name}
                                    </p>
                                    <span className="text-xs text-gray-500">{dateTime(s.sold_at)}</span>
                                </div>
                                <p className="text-xs text-gray-600">
                                    {s.movements?.map((m) => `${qty(m.quantity, m.unit)} ${m.ingredient_name}`).join(', ')}
                                </p>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <Empty>No sales yet.</Empty>
                )}
            </Card>
        </div>
    );
}
