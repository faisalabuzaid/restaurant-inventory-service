import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { api, keys } from '@/api/client';
import { Card, Empty, ErrorBanner, StatusBadge } from '@/components/ui';
import { qty, secondsSince } from '@/lib/format';

const POLL_MS = 5000;

/** Re-render once a second so "updated Ns ago" ticks. */
function useNow() {
    const [now, setNow] = useState(Date.now());
    useEffect(() => {
        const id = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(id);
    }, []);
    return now;
}

export function StockPage() {
    const now = useNow();

    const stock = useQuery({
        queryKey: keys.stock,
        queryFn: api.stock.list,
        refetchInterval: POLL_MS,
    });
    const openOrders = useQuery({
        queryKey: [...keys.purchaseOrders, { onlyOpen: true }],
        queryFn: () => api.purchaseOrders.list(true),
        refetchInterval: POLL_MS,
    });

    const age = secondsSince(stock.data?.meta.generated_at, now);
    const negatives = stock.data?.data.filter((s) => s.is_negative) ?? [];
    const awaiting = (openOrders.data ?? []).filter((o) => o.status !== 'draft');

    return (
        <div className="space-y-6">
            <div className="grid gap-3 sm:grid-cols-3">
                <Tile label="Ingredients tracked" value={stock.data?.data.length ?? '-'} />
                <Tile label="Below zero" value={negatives.length} tone={negatives.length ? 'bad' : 'good'} />
                <Tile label="Orders awaiting delivery" value={awaiting.length} />
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                <Card
                    title="Current stock"
                    actions={
                        <div className="flex items-center gap-3 text-xs text-gray-500">
                            <span aria-live="polite">
                                {stock.isFetching ? 'Refreshing...' : age === null ? '' : `Updated ${age}s ago`}
                                {!stock.isFetching && age !== null && ` · auto-refresh every ${POLL_MS / 1000}s`}
                            </span>
                            <button type="button" className="btn btn-secondary" onClick={() => stock.refetch()} disabled={stock.isFetching}>
                                Refresh
                            </button>
                        </div>
                    }
                >
                    {stock.error && <ErrorBanner error={stock.error} />}
                    {stock.isLoading ? (
                        <Empty>Loading...</Empty>
                    ) : stock.data?.data.length ? (
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Ingredient</th>
                                    <th className="text-right">In stock</th>
                                    <th className="text-right">On order</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {stock.data.data.map((s) => (
                                    <tr key={s.ingredient_id} className={s.is_negative ? 'bg-red-50' : ''}>
                                        <td className={s.is_negative ? 'font-medium text-red-800' : ''}>{s.name}</td>
                                        <td className={`text-right tabular-nums ${s.is_negative ? 'font-semibold text-red-700' : ''}`}>
                                            {qty(s.current_stock, s.unit)}
                                        </td>
                                        <td className="text-right text-gray-600 tabular-nums">
                                            {s.on_order > 0 ? qty(s.on_order, s.unit) : <span className="text-gray-300">-</span>}
                                        </td>
                                        <td className="text-right">
                                            {s.is_negative && <span className="badge bg-red-100 text-red-800">Negative: recount needed</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <Empty>No ingredients yet. Add some under "Ingredients & suppliers".</Empty>
                    )}
                    <p className="mt-3 text-xs text-gray-500">
                        Stock is the sum of every recorded delivery and sale. A negative number means more was sold than
                        was ever recorded as delivered; the storeroom and the records disagree.
                    </p>
                </Card>

                <Card title="Open purchase orders">
                    {openOrders.isLoading ? (
                        <Empty>Loading...</Empty>
                    ) : openOrders.data?.length ? (
                        <ul className="divide-y divide-gray-100">
                            {openOrders.data.map((o) => (
                                <li key={o.id} className="py-2.5">
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="text-sm font-medium">
                                            #{o.id} {o.supplier_name}
                                        </p>
                                        <StatusBadge status={o.status} label={o.status_label} />
                                    </div>
                                    <ul className="mt-1 text-xs text-gray-600">
                                        {o.lines.map((l) => (
                                            <li key={l.id} className="flex justify-between">
                                                <span>{l.ingredient_name}</span>
                                                <span className="tabular-nums">
                                                    {o.status === 'draft'
                                                        ? `${qty(l.quantity_ordered, l.unit)} (not sent)`
                                                        : `${qty(l.outstanding, l.unit)} outstanding`}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <Empty>No open orders.</Empty>
                    )}
                </Card>
            </div>
        </div>
    );
}

function Tile({ label, value, tone }: { label: string; value: number | string; tone?: 'good' | 'bad' }) {
    const color = tone === 'bad' ? 'text-red-700' : tone === 'good' ? 'text-green-700' : 'text-gray-900';
    return (
        <div className="card">
            <p className="text-xs font-medium text-gray-500">{label}</p>
            <p className={`mt-1 text-2xl font-semibold tabular-nums ${color}`}>{value}</p>
        </div>
    );
}
