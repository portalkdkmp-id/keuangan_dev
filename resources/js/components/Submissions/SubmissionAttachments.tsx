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
                    <div key={attachment.id} className="flex items-center justify-between rounded-md border p-3 text-sm">
                        <Link href={`/submission-attachments/${attachment.id}/download`}>{attachment.original_name}</Link>
                        {editable && <Button size="sm" variant="outline" onClick={() => router.delete(`/submission-attachments/${attachment.id}`)}>Delete</Button>}
                    </div>
                ))}
            </div>
        </div>
    );
}
