import { MapPin } from 'lucide-react';

export function CooperativeRegion({ cooperative }: { cooperative?: any }) {
    const parts = [
        cooperative?.village?.name,
        cooperative?.district?.name,
        cooperative?.city?.name,
        cooperative?.province?.name,
    ].filter(Boolean);

    return (
        <div className="min-h-16 border-l-2 border-primary/30 pl-3 text-sm">
            <div className="flex items-center gap-2 font-medium"><MapPin className="size-4" />Region Koperasi</div>
            <p className="mt-1 text-muted-foreground">
                {parts.length ? parts.join(', ') : 'Pilih koperasi untuk melihat region.'}
            </p>
        </div>
    );
}
