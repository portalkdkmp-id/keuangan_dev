import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('success', (event) => {
            const props = event.detail.page.props as {
                flash?: {
                    success?: string | null;
                    warning?: string | null;
                    error?: string | null;
                };
                errors?: Record<string, string>;
            };

            if (props.flash?.success) {
                toast.success(props.flash.success);
            }

            if (props.flash?.warning) {
                toast.warning(props.flash.warning);
            }

            if (props.flash?.error) {
                toast.error(props.flash.error);
            }

            const firstError = Object.values(props.errors ?? {})[0];
            if (firstError) {
                toast.error(firstError);
            }
        });
    }, []);
}
