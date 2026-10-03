import type { ApiError } from '@/api/client';
import type { Ingredient } from '@/api/types';

/** Editable "ingredient + quantity" rows; quantities kept as strings while typing. */
export interface LineDraft {
    ingredient_id: string;
    quantity: string;
}

export const emptyLine = (): LineDraft => ({ ingredient_id: '', quantity: '' });

/** Convert drafts to the API payload. Blank rows are dropped so an unused trailing row never fails validation. */
export function toPayload(lines: LineDraft[]) {
    return lines
        .filter((l) => l.ingredient_id !== '' || l.quantity !== '')
        .map((l) => ({ ingredient_id: Number(l.ingredient_id), quantity: Number(l.quantity) }));
}

interface Props {
    ingredients: Ingredient[];
    lines: LineDraft[];
    onChange: (lines: LineDraft[]) => void;
    error?: ApiError | null;
    /** Validation key prefix on the server, defaults to "lines". */
    prefix?: string;
}

export function QuantityLinesEditor({ ingredients, lines, onChange, error, prefix = 'lines' }: Props) {
    const update = (index: number, patch: Partial<LineDraft>) =>
        onChange(lines.map((l, i) => (i === index ? { ...l, ...patch } : l)));

    const remove = (index: number) => onChange(lines.filter((_, i) => i !== index));

    const unitFor = (id: string) => ingredients.find((i) => String(i.id) === id)?.unit ?? '';
    // Server indexes refer to the payload after blank rows are dropped.
    const payloadIndex = (index: number) =>
        lines.slice(0, index).filter((l) => l.ingredient_id !== '' || l.quantity !== '').length;

    return (
        <div className="space-y-2">
            {error?.field(prefix) && <p className="text-xs text-red-600">{error.field(prefix)}</p>}
            {lines.map((line, index) => {
                const pi = payloadIndex(index);
                const ingredientError = error?.field(`${prefix}.${pi}.ingredient_id`);
                const quantityError = error?.field(`${prefix}.${pi}.quantity`);
                return (
                    <div key={index} className="grid grid-cols-[1fr_8rem_3rem_2rem] items-start gap-2">
                        <div>
                            <select
                                className="input"
                                value={line.ingredient_id}
                                onChange={(e) => update(index, { ingredient_id: e.target.value })}
                                aria-label="Ingredient"
                            >
                                <option value="">Select ingredient</option>
                                {ingredients.map((i) => (
                                    <option key={i.id} value={i.id}>
                                        {i.name}
                                    </option>
                                ))}
                            </select>
                            {ingredientError && <p className="mt-1 text-xs text-red-600">{ingredientError}</p>}
                        </div>
                        <div>
                            <input
                                className="input"
                                type="number"
                                min="0"
                                step="0.001"
                                placeholder="Qty"
                                value={line.quantity}
                                onChange={(e) => update(index, { quantity: e.target.value })}
                                aria-label="Quantity"
                            />
                            {quantityError && <p className="mt-1 text-xs text-red-600">{quantityError}</p>}
                        </div>
                        <span className="pt-2 text-xs text-gray-500">{unitFor(line.ingredient_id)}</span>
                        <button
                            type="button"
                            className="pt-1.5 text-gray-400 hover:text-red-600"
                            onClick={() => remove(index)}
                            aria-label="Remove line"
                            disabled={lines.length === 1}
                        >
                            x
                        </button>
                    </div>
                );
            })}
            <button type="button" className="btn btn-secondary" onClick={() => onChange([...lines, emptyLine()])}>
                + Add line
            </button>
        </div>
    );
}
