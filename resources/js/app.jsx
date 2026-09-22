import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import NetworkStatusIndicator from './Components/NetworkStatusIndicator';
import './i18n';

createInertiaApp({
    progress: {
        color: '#2563eb',
        showSpinner: true,
    },
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.jsx', { eager: true });
        const page = pages[`./pages/${name}.jsx`];
        if (!page) {
            throw new Error(`Inertia page not found: ./pages/${name}.jsx`);
        }
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <NetworkStatusIndicator />
                <App {...props} />
            </>
        );
    },
});
