import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import App from './App';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            // Lists are cheap and the server is local; always refetch on focus
            // so the screen never shows stale stock after switching windows.
            staleTime: 0,
            refetchOnWindowFocus: true,
            retry: 1,
        },
    },
});

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <QueryClientProvider client={queryClient}>
            <App />
        </QueryClientProvider>
    </StrictMode>,
);
