import { useState } from 'react';
import { CatalogPage } from '@/pages/CatalogPage';
import { MenuPage } from '@/pages/MenuPage';

type Tab = 'stock' | 'purchasing' | 'menu' | 'catalog' | 'pos';

const tabs: Array<{ id: Tab; label: string }> = [
    { id: 'stock', label: 'Stock' },
    { id: 'purchasing', label: 'Purchase orders' },
    { id: 'menu', label: 'Menu & recipes' },
    { id: 'catalog', label: 'Ingredients & suppliers' },
    { id: 'pos', label: 'POS' },
];

export default function App() {
    const [tab, setTab] = useState<Tab>('stock');

    return (
        <div className="mx-auto max-w-6xl px-4 py-6">
            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-xl font-semibold">Restaurant Inventory</h1>
                    <p className="text-sm text-gray-500">Single branch. Stock, purchasing and POS sales.</p>
                </div>
                <nav className="flex flex-wrap gap-1 rounded-lg bg-gray-200 p-1" aria-label="Sections">
                    {tabs.map((t) => (
                        <button
                            key={t.id}
                            type="button"
                            onClick={() => setTab(t.id)}
                            aria-current={tab === t.id ? 'page' : undefined}
                            className={`rounded-md px-3 py-1.5 text-sm font-medium transition ${
                                tab === t.id ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </nav>
            </header>

            <main>
                {tab === 'catalog' && <CatalogPage />}
                {tab === 'menu' && <MenuPage />}
                {(tab === 'stock' || tab === 'purchasing' || tab === 'pos') && (
                    <p className="text-sm text-gray-500">{tabs.find((t) => t.id === tab)?.label} coming next.</p>
                )}
            </main>
        </div>
    );
}
