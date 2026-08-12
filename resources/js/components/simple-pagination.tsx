import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function SimplePagination({ meta }: { meta: any }) {
    if (!meta || meta.last_page <= 1) return null;

    return (
        <div className="flex items-center gap-2">
            <Button
                type="button"
                size="icon"
                variant="outline"
                disabled={!meta.prev_page_url}
                onClick={() =>
                    meta.prev_page_url &&
                    router.get(
                        meta.prev_page_url,
                        {},
                        { preserveState: true, preserveScroll: true },
                    )
                }
                title="Halaman sebelumnya"
            >
                <ChevronLeft className="size-4" />
            </Button>
            <span className="text-sm text-muted-foreground">
                Halaman {meta.current_page} dari {meta.last_page}
            </span>
            <Button
                type="button"
                size="icon"
                variant="outline"
                disabled={!meta.next_page_url}
                onClick={() =>
                    meta.next_page_url &&
                    router.get(
                        meta.next_page_url,
                        {},
                        { preserveState: true, preserveScroll: true },
                    )
                }
                title="Halaman berikutnya"
            >
                <ChevronRight className="size-4" />
            </Button>
        </div>
    );
}
