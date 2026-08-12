import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function SimplePagination({ meta }: { meta: any }) {
    if (!meta || meta.last_page <= 1) return null;

    return (
        <div className="flex items-center justify-between gap-3 text-sm">
            <span className="text-muted-foreground">
                Menampilkan {meta.from ?? 0}-{meta.to ?? 0} dari {meta.total}
            </span>
            <div className="flex gap-2">
                <Button
                    asChild
                    size="icon"
                    variant="outline"
                    disabled={!meta.prev_page_url}
                >
                    <Link
                        href={meta.prev_page_url ?? '#'}
                        preserveScroll
                        preserveState
                        aria-label="Halaman sebelumnya"
                    >
                        <ChevronLeft className="size-4" />
                    </Link>
                </Button>
                <span className="flex h-9 items-center px-2">
                    {meta.current_page} / {meta.last_page}
                </span>
                <Button
                    asChild
                    size="icon"
                    variant="outline"
                    disabled={!meta.next_page_url}
                >
                    <Link
                        href={meta.next_page_url ?? '#'}
                        preserveScroll
                        preserveState
                        aria-label="Halaman berikutnya"
                    >
                        <ChevronRight className="size-4" />
                    </Link>
                </Button>
            </div>
        </div>
    );
}
