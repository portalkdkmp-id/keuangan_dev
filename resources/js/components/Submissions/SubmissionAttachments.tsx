import { Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardTitle } from '../ui/card';

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
                <Card className="p-3 text-sm">
                    <CardTitle>Lampiran Pengajuan</CardTitle>
                    <CardContent>
                        {submission.attachments?.length === 0 && <p className="text-sm text-muted-foreground">Belum ada lampiran pengajuan.</p>}
                        {submission.attachments?.map((attachment: any) => (
                            <div key={attachment.id} className="">
                                {attachment.mime_type?.startsWith('image/') && <img src={`/submission-attachments/${attachment.id}/preview`} alt={attachment.original_name} className="max-h-80 w-full rounded-md object-contain" />}
                                <div className="flex items-center justify-between gap-2">
                                    <Link href={`/submission-attachments/${attachment.id}/download`}>{attachment.original_name}</Link>
                                    {editable && <Button size="sm" variant="outline" onClick={() => router.delete(`/submission-attachments/${attachment.id}`)}>Delete</Button>}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
