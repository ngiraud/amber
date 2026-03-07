declare global {
    interface Window {
        Native?: {
            on: (event: string, callback: (payload: unknown) => void) => void;
        };
    }
}

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

import type { Session } from './resources';

declare module '@inertiajs/core' {
    interface InertiaFlashData {
        success?: string;
        error?: string;
    }

    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            display_timezone: string;
            display_locale: string;
            activeSession: Session | null;
            [key: string]: unknown;
        };
    }
}
