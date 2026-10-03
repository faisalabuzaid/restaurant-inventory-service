import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, ApiError, keys } from '@/api/client';
import type { Ingredient, MenuItem } from '@/api/types';
import { emptyLine, QuantityLinesEditor, toPayload, type LineDraft } from '@/components/QuantityLinesEditor';
import { Card, Empty, ErrorBanner, Field } from '@/components/ui';
import { qty } from '@/lib/format';

export function MenuPage() {
    const ingredients = useQuery({ queryKey: keys.ingredients, queryFn: api.ingredients.list });
    const menuItems = useQuery({ queryKey: keys.menuItems, queryFn: api.menuItems.list });
    const [editing, setEditing] = useState<MenuItem | null>(null);

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_minmax(0,1.3fr)]">
            <div className="space-y-6">
                <CreateMenuItemForm ingredientsAvailable={ingredients.data ?? []} />
                {editing && (
                    <EditRecipeForm
                        key={editing.id}
                        item={editing}
                        ingredientsAvailable={ingredients.data ?? []}
                        onDone={() => setEditing(null)}
                    />
                )}
            </div>

            <Card title={`Menu items (${menuItems.data?.length ?? 0})`}>
                {menuItems.isLoading ? (
                    <Empty>Loading...</Empty>
                ) : menuItems.data?.length ? (
                    <ul className="divide-y divide-gray-100">
                        {menuItems.data.map((item) => (
                            <li key={item.id} className="flex items-start justify-between gap-4 py-3">
                                <div>
                                    <p className="font-medium">{item.name}</p>
                                    <p className="text-sm text-gray-600">
                                        {item.recipe.map((r) => `${qty(r.quantity, r.unit)} ${r.ingredient_name}`).join(' + ')}
                                    </p>
                                </div>
                                <button type="button" className="btn btn-secondary" onClick={() => setEditing(item)}>
                                    Edit recipe
                                </button>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <Empty>No menu items yet. Create one with its recipe on the left.</Empty>
                )}
            </Card>
        </div>
    );
}

function CreateMenuItemForm({ ingredientsAvailable }: { ingredientsAvailable: Ingredient[] }) {
    const queryClient = useQueryClient();
    const [name, setName] = useState('');
    const [lines, setLines] = useState<LineDraft[]>([emptyLine()]);

    const create = useMutation({
        mutationFn: api.menuItems.create,
        onSuccess: () => {
            setName('');
            setLines([emptyLine()]);
            queryClient.invalidateQueries({ queryKey: keys.menuItems });
        },
    });
    const error = create.error instanceof ApiError ? create.error : null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        create.mutate({ name: name.trim(), lines: toPayload(lines) });
    };

    return (
        <Card title="New menu item">
            <form onSubmit={submit} className="space-y-3">
                <Field label="Name" error={error?.field('name')}>
                    <input className="input" value={name} onChange={(e) => setName(e.target.value)} required />
                </Field>
                <div>
                    <span className="label">Recipe (per one sold)</span>
                    <QuantityLinesEditor ingredients={ingredientsAvailable} lines={lines} onChange={setLines} error={error} />
                </div>
                {error && !Object.keys(error.errors).length && <ErrorBanner error={error} />}
                <button type="submit" className="btn btn-primary" disabled={create.isPending}>
                    Create menu item
                </button>
            </form>
        </Card>
    );
}

function EditRecipeForm({
    item,
    ingredientsAvailable,
    onDone,
}: {
    item: MenuItem;
    ingredientsAvailable: Ingredient[];
    onDone: () => void;
}) {
    const queryClient = useQueryClient();
    const [lines, setLines] = useState<LineDraft[]>(
        item.recipe.map((r) => ({ ingredient_id: String(r.ingredient_id), quantity: String(r.quantity) })),
    );

    const update = useMutation({
        mutationFn: (payload: ReturnType<typeof toPayload>) => api.menuItems.updateRecipe(item.id, payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: keys.menuItems });
            onDone();
        },
    });
    const error = update.error instanceof ApiError ? update.error : null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        update.mutate(toPayload(lines));
    };

    return (
        <Card
            title={`Edit recipe: ${item.name}`}
            actions={
                <button type="button" className="text-sm text-gray-500 hover:text-gray-900" onClick={onDone}>
                    Cancel
                </button>
            }
        >
            <form onSubmit={submit} className="space-y-3">
                <QuantityLinesEditor ingredients={ingredientsAvailable} lines={lines} onChange={setLines} error={error} />
                {error && !Object.keys(error.errors).length && <ErrorBanner error={error} />}
                <p className="text-xs text-gray-500">
                    Changing a recipe only affects future sales; recorded stock movements are not rewritten.
                </p>
                <button type="submit" className="btn btn-primary" disabled={update.isPending}>
                    Save recipe
                </button>
            </form>
        </Card>
    );
}