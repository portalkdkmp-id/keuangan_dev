export type * from './auth';
export type * from './navigation';
export type * from './ui';

export type SharedData = {
    name: string;
    auth: import('./auth').Auth;
    flash?: {
        success?: string | null;
        error?: string | null;
        warning?: string | null;
    };
    sidebarOpen: boolean;
};
