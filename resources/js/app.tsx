import { createInertiaApp } from '@inertiajs/react';
import { initializeTheme } from '@/hooks/use-appearance';
import { disableNumberInputWheel } from '@/lib/disable-number-input-wheel';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

initializeTheme();
disableNumberInputWheel();

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4F46E5',
    },
});
