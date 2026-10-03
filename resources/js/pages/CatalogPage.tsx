import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, ApiError, keys } from '@/api/client';
import { UNITS, type Unit } from '@/api/types';
import { Card, Empty, ErrorBanner, Field } from '@/components/ui';
import { dateTime } from '@/lib/format';

export function CatalogPage() {
    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <IngredientsPanel />
            <SuppliersPanel />
        </div>
    );
}

function IngredientsPanel() {
    const queryClient = useQueryClient();
    const ingredients = useQuery({ queryKey: keys.ingredients, queryFn: api.ingredients.list });

    const [name, setName] = useState('');
    const [unit, setUnit] = useState<Unit>('g');

    const create = useMutation({
        mutationFn: api.ingredients.create,
        onSuccess: () => {
            setName('');
            queryClient.invalidateQueries({ queryKey: keys.ingredients });
            queryClient.invalidateQueries({ queryKey: keys.stock });
        },
    });

    const error = create.error instanceof ApiError ? create.error : null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        create.mutate({ name: name.trim(), unit });
    };

    return (
        <Card title={`Ingredients (${ingredients.data?.length ?? 0})`}>
            <form onSubmit={submit} className="mb-4 grid grid-cols-[1fr_6rem_auto] items-end gap-2">
                <Field label="Name" error={error?.field('name')}>
                    <input className="input" value={name} onChange={(e) => setName(e.target.value)} required />
                </Field>
                <Field label="Unit" error={error?.field('unit')}>
                    <select className="input" value={unit} onChange={(e) => setUnit(e.target.value as Unit)}>
                        {UNITS.map((u) => (
                            <option key={u} value={u}>
                                {u}
                            </option>
                        ))}
                    </select>
                </Field>
                <button type="submit" className="btn btn-primary" disabled={create.isPending}>
                    Add
                </button>
            </form>
            {error && !error.field('name') && !error.field('unit') && <ErrorBanner error={error} />}

            {ingredients.isLoading ? (
                <Empty>Loading...</Empty>
            ) : ingredients.data?.length ? (
                <table className="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Unit</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        {ingredients.data.map((i) => (
                            <tr key={i.id}>
                                <td>{i.name}</td>
                                <td className="text-gray-600">{i.unit}</td>
                                <td className="text-gray-500">{dateTime(i.created_at)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            ) : (
                <Empty>No ingredients yet. Add the first one above.</Empty>
            )}
        </Card>
    );
}

function SuppliersPanel() {
    const queryClient = useQueryClient();
    const suppliers = useQuery({ queryKey: keys.suppliers, queryFn: api.suppliers.list });

    const [name, setName] = useState('');
    const [contact, setContact] = useState('');

    const create = useMutation({
        mutationFn: api.suppliers.create,
        onSuccess: () => {
            setName('');
            setContact('');
            queryClient.invalidateQueries({ queryKey: keys.suppliers });
        },
    });

    const error = create.error instanceof ApiError ? create.error : null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        create.mutate({ name: name.trim(), contact: contact.trim() || undefined });
    };

    return (
        <Card title={`Suppliers (${suppliers.data?.length ?? 0})`}>
            <form onSubmit={submit} className="mb-4 grid grid-cols-[1fr_1fr_auto] items-end gap-2">
                <Field label="Name" error={error?.field('name')}>
                    <input className="input" value={name} onChange={(e) => setName(e.target.value)} required />
                </Field>
                <Field label="Contact (optional)" error={error?.field('contact')}>
                    <input className="input" value={contact} onChange={(e) => setContact(e.target.value)} />
                </Field>
                <button type="submit" className="btn btn-primary" disabled={create.isPending}>
                    Add
                </button>
            </form>
            {error && !error.field('name') && !error.field('contact') && <ErrorBanner error={error} />}

            {suppliers.isLoading ? (
                <Empty>Loading...</Empty>
            ) : suppliers.data?.length ? (
                <table className="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        {suppliers.data.map((s) => (
                            <tr key={s.id}>
                                <td>{s.name}</td>
                                <td className="text-gray-600">{s.contact ?? '-'}</td>
                                <td className="text-gray-500">{dateTime(s.created_at)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            ) : (
                <Empty>No suppliers yet.</Empty>
            )}
        </Card>
    );
}
