/// <reference types="vite/client" />
import type { AxiosInstance } from 'axios';

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
    export default component;
}

interface ZiggyRouterHelper {
    current: (name?: string, params?: Record<string, unknown>) => boolean;
}

declare global {
    function route(): ZiggyRouterHelper;
    function route(name: string, params?: Record<string, unknown> | number | string | Array<unknown>, absolute?: boolean): string;

    interface Window {
        axios: AxiosInstance;
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof globalThis.route;
    }
}

export {};
