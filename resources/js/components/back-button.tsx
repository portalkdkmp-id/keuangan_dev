import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export function BackButton({ fallback = '/' }: { fallback?: string }) {
    return <Button type="button" variant="outline" onClick={() => {
        if (window.history.length > 1) {
            window.history.back();

            return;
        }

        router.visit(fallback);
    }}>Kembali</Button>;
}
