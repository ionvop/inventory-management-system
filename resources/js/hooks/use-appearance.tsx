import { useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

const STORAGE_KEY = 'appearance';
const COOKIE_NAME = 'appearance';
const COOKIE_MAX_AGE = 365 * 24 * 60 * 60;

const listeners = new Set<() => void>();

let currentAppearance: Appearance = 'system';

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (value: string): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.cookie = `${COOKIE_NAME}=${value};path=/;max-age=${COOKIE_MAX_AGE};SameSite=Lax`;
};

const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') {
        return 'system';
    }

    const stored = window.localStorage.getItem(STORAGE_KEY);

    if (stored === 'light' || stored === 'dark' || stored === 'system') {
        return stored;
    }

    return 'system';
};

const isDarkMode = (appearance: Appearance): boolean =>
    appearance === 'dark' || (appearance === 'system' && prefersDark());

const applyTheme = (appearance: Appearance): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const isDark = isDarkMode(appearance);

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const subscribe = (callback: () => void): (() => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

const mediaQuery = (): MediaQueryList | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = (): void => applyTheme(currentAppearance);

/**
 * Apply the persisted appearance and start listening for OS theme changes.
 * Call once, before the app renders.
 */
export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (!window.localStorage.getItem(STORAGE_KEY)) {
        window.localStorage.setItem(STORAGE_KEY, 'system');
        setCookie('system');
    }

    currentAppearance = getStoredAppearance();
    applyTheme(currentAppearance);

    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance(): UseAppearanceReturn {
    const appearance = useSyncExternalStore(
        subscribe,
        () => currentAppearance,
        () => 'system' as Appearance,
    );

    const resolvedAppearance: ResolvedAppearance = isDarkMode(appearance)
        ? 'dark'
        : 'light';

    const updateAppearance = (mode: Appearance): void => {
        currentAppearance = mode;

        // Persist client-side for instant reads on the next visit.
        window.localStorage.setItem(STORAGE_KEY, mode);

        // Persist in a cookie so the server can render the correct theme.
        setCookie(mode);

        applyTheme(mode);
        notify();
    };

    return { appearance, resolvedAppearance, updateAppearance } as const;
}
