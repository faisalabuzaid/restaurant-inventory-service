// Response shapes of /api/*. Kept in one place and mirrored by the
// Laravel API Resources; if a Resource changes, change it here too.

export type Unit = 'g' | 'kg' | 'ml' | 'l' | 'pcs';
export const UNITS: Unit[] = ['g', 'kg', 'ml', 'l', 'pcs'];

export type PurchaseOrderStatus = 'draft' | 'sent' | 'received' | 'closed';

export interface Ingredient {
    id: number;
    name: string;
    unit: Unit;
    created_at: string;
}

export interface Supplier {
    id: number;
    name: string;
    contact: string | null;
    created_at: string;
}

export interface RecipeLine {
    ingredient_id: number;
    ingredient_name: string;
    unit: Unit;
    quantity: number;
}

export interface MenuItem {
    id: number;
    name: string;
    recipe: RecipeLine[];
    created_at: string;
}

export interface PurchaseOrderLine {
    id: number;
    ingredient_id: number;
    ingredient_name: string;
    unit: Unit;
    quantity_ordered: number;
    quantity_received: number;
    outstanding: number;
}

export interface DeliveryLine {
    purchase_order_line_id: number;
    ingredient_id: number;
    ingredient_name: string;
    unit: Unit;
    quantity: number;
}

export interface Delivery {
    id: number;
    purchase_order_id: number;
    received_at: string;
    note: string | null;
    lines: DeliveryLine[];
}

export interface PurchaseOrder {
    id: number;
    supplier_id: number;
    supplier_name: string;
    status: PurchaseOrderStatus;
    status_label: string;
    is_open: boolean;
    accepts_deliveries: boolean;
    notes: string | null;
    lines: PurchaseOrderLine[];
    deliveries: Delivery[];
    sent_at: string | null;
    closed_at: string | null;
    created_at: string;
}

export interface StockLevel {
    ingredient_id: number;
    name: string;
    unit: Unit;
    current_stock: number;
    is_negative: boolean;
    on_order: number;
}

export interface StockResponse {
    data: StockLevel[];
    meta: { generated_at: string };
}

export interface SaleMovement {
    ingredient_id: number;
    ingredient_name: string;
    unit: Unit;
    quantity: number;
}

export interface Sale {
    id: number;
    menu_item_id: number;
    menu_item_name: string;
    quantity: number;
    idempotency_key: string | null;
    sold_at: string;
    movements?: SaleMovement[];
}

export interface SaleWarning {
    ingredient_id: number;
    ingredient_name: string;
    unit: Unit;
    stock_after: number;
}

export interface SaleResponse {
    data: Sale;
    consumed: Array<SaleWarning & { quantity: number }>;
    warnings: SaleWarning[];
    replayed: boolean;
}

// Request payloads

export interface QuantityLineInput {
    ingredient_id: number;
    quantity: number;
}

export interface CreatePurchaseOrderInput {
    supplier_id: number;
    notes?: string;
    lines: QuantityLineInput[];
}

export interface DeliveryLineInput {
    purchase_order_line_id: number;
    quantity: number;
}

export interface RecordDeliveryInput {
    received_at?: string;
    note?: string;
    lines: DeliveryLineInput[];
}

export interface RecordSaleInput {
    menu_item_id: number;
    quantity: number;
    idempotency_key?: string;
}
