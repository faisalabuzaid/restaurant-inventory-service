import type {
    CreatePurchaseOrderInput,
    Ingredient,
    MenuItem,
    PurchaseOrder,
    QuantityLineInput,
    RecordDeliveryInput,
    RecordSaleInput,
    Sale,
    SaleResponse,
    StockResponse,
    Supplier,
    Unit,
} from './types';

/**
 * Error raised for any non-2xx response. Carries the Laravel error shape:
 * `message`, optional `errors` (422 validation, keyed by field) and optional
 * `error` code (domain errors such as over_receipt / invalid_state_transition).
 */
export class ApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
        public readonly code?: string,
        public readonly errors: Record<string, string[]> = {},
        public readonly body: Record<string, unknown> = {},
    ) {
        super(message);
        this.name = 'ApiError';
    }

    /** First validation message for a field (supports dotted paths like lines.0.quantity). */
    field(name: string): string | undefined {
        return this.errors[name]?.[0];
    }

    /** All validation messages flattened, for a summary list. */
    allMessages(): string[] {
        const flat = Object.values(this.errors).flat();
        return flat.length ? flat : [this.message];
    }
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
    const response = await fetch(`/api${path}`, {
        method,
        headers: {
            Accept: 'application/json',
            ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    const text = await response.text();
    const json = text ? (JSON.parse(text) as Record<string, unknown>) : {};

    if (!response.ok) {
        throw new ApiError(
            response.status,
            typeof json.message === 'string' ? json.message : `Request failed (${response.status})`,
            typeof json.error === 'string' ? json.error : undefined,
            (json.errors as Record<string, string[]>) ?? {},
            json,
        );
    }

    return json as T;
}

const get = <T>(path: string) => request<T>('GET', path);
const post = <T>(path: string, body?: unknown) => request<T>('POST', path, body);
const put = <T>(path: string, body?: unknown) => request<T>('PUT', path, body);

type Wrapped<T> = { data: T };

export const api = {
    ingredients: {
        list: () => get<Wrapped<Ingredient[]>>('/ingredients').then((r) => r.data),
        create: (input: { name: string; unit: Unit }) =>
            post<Wrapped<Ingredient>>('/ingredients', input).then((r) => r.data),
    },
    suppliers: {
        list: () => get<Wrapped<Supplier[]>>('/suppliers').then((r) => r.data),
        create: (input: { name: string; contact?: string }) =>
            post<Wrapped<Supplier>>('/suppliers', input).then((r) => r.data),
    },
    menuItems: {
        list: () => get<Wrapped<MenuItem[]>>('/menu-items').then((r) => r.data),
        create: (input: { name: string; lines: QuantityLineInput[] }) =>
            post<Wrapped<MenuItem>>('/menu-items', input).then((r) => r.data),
        updateRecipe: (id: number, lines: QuantityLineInput[]) =>
            put<Wrapped<MenuItem>>(`/menu-items/${id}/recipe`, { lines }).then((r) => r.data),
    },
    purchaseOrders: {
        list: (onlyOpen = false) =>
            get<Wrapped<PurchaseOrder[]>>(`/purchase-orders${onlyOpen ? '?open=1' : ''}`).then((r) => r.data),
        get: (id: number) => get<Wrapped<PurchaseOrder>>(`/purchase-orders/${id}`).then((r) => r.data),
        create: (input: CreatePurchaseOrderInput) =>
            post<Wrapped<PurchaseOrder>>('/purchase-orders', input).then((r) => r.data),
        send: (id: number) => post<Wrapped<PurchaseOrder>>(`/purchase-orders/${id}/send`).then((r) => r.data),
        recordDelivery: (id: number, input: RecordDeliveryInput) =>
            post<Wrapped<PurchaseOrder>>(`/purchase-orders/${id}/deliveries`, input).then((r) => r.data),
    },
    stock: {
        list: () => get<StockResponse>('/stock'),
    },
    sales: {
        list: () => get<Wrapped<Sale[]>>('/sales').then((r) => r.data),
        record: (input: RecordSaleInput) => post<SaleResponse>('/sales', input),
    },
};

/** Query keys, centralised so invalidation after mutations is consistent. */
export const keys = {
    ingredients: ['ingredients'] as const,
    suppliers: ['suppliers'] as const,
    menuItems: ['menu-items'] as const,
    purchaseOrders: ['purchase-orders'] as const,
    stock: ['stock'] as const,
    sales: ['sales'] as const,
};
