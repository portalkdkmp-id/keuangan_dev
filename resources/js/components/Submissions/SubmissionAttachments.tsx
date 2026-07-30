import { Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export function SubmissionAttachments({ submission, editable = false }: { submission: any; editable?: boolean }) {
    const form = useForm({ file: null as File | null });
    return (
        <div className="space-y-3">
            {editable && (
                <form onSubmit={(e) => { e.preventDefault(); form.post(`/submissions/${submission.id}/attachments`, { forceFormData: true }); }} className="flex gap-2">
                    <Input type="file" onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)} />
                    <Button type="submit">Upload</Button>
                </form>
            )}
            <div className="space-y-2">
                {submission.attachments?.map((attachment: any) => (
                    <div key={attachment.id} className="space-y-3 rounded-md border p-3 text-sm">
                        <h2 className="font-semibold">Bukti Pengajuan</h2>
                        {attachment.mime_type?.startsWith('image/') && <img src={`/submission-attachments/${attachment.id}/preview`} alt={attachment.original_name} className="max-h-80 w-full rounded-md object-contain" />}
                        <div className="flex items-center justify-between gap-2">
                            <Link href={`/submission-attachments/${attachment.id}/download`}>{attachment.original_name}</Link>
                            {editable && <Button size="sm" variant="outline" onClick={() => router.delete(`/submission-attachments/${attachment.id}`)}>Delete</Button>}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
