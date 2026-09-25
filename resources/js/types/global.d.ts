declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            activeProfile: {
                id: number;
                name: string;
                role: 'staff' | 'supervisor' | 'administrator';
            } | null;
            [key: string]: unknown;
        };
    }
}

export {};
