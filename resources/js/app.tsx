import { createInertiaApp } from '@inertiajs/react';
import { createRoot, type Root } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function AppToasts() {
    useFlashToast();

    return <Toaster position="top-center" richColors closeButton />;
}

declare global {
    interface Window {
        __inertiaReactRoot?: Root;
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    setup({ el, App, props }) {
        if (!el) {
            throw new Error('Inertia root element was not found.');
        }

        const app = (
            <TooltipProvider delayDuration={0}>
                <App {...props} />
                <AppToasts />
            </TooltipProvider>
        );

        if (!window.__inertiaReactRoot) {
            window.__inertiaReactRoot = createRoot(el);
        }

        window.__inertiaReactRoot.render(app);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
