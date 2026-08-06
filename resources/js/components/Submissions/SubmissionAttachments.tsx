import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { FileText, ZoomIn } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardTitle } from '../ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '../ui/dialog';

export function SubmissionAttachments({
    submission,
    editable = false,
}: {
    submission: any;
    editable?: boolean;
}) {
    const form = useForm({ file: null as File | null });
    const [preview, setPreview] = useState<any>(null);
    return (
        <div className="space-y-3">
            {editable && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(`/submissions/${submission.id}/attachments`, {
                            forceFormData: true,
                        });
                    }}
                    className="flex gap-2"
                >
                    <Input
                        type="file"
                        onChange={(e) =>
                            form.setData('file', e.target.files?.[0] ?? null)
                        }
                    />
                    <Button type="submit">Upload</Button>
                </form>
            )}
            <div className="space-y-2">
                <Card className="p-3 text-sm">
                    <CardTitle>Lampiran Pengajuan</CardTitle>
                    <CardContent className="flex flex-wrap gap-3">
                        {submission.attachments?.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Belum ada lampiran pengajuan.
                            </p>
                        )}
                        {submission.attachments?.map((attachment: any) => (
                            <div
                                key={attachment.id}
                                className="flex flex-col items-center justify-start gap-2"
                            >
                                {attachment.mime_type?.startsWith('image/') ? (
                                    <button
                                        type="button"
                                        className="group relative overflow-hidden rounded-md border"
                                        onClick={() => setPreview(attachment)}
                                    >
                                        <img
                                            src={`/submission-attachments/${attachment.id}/preview`}
                                            alt={attachment.original_name}
                                            className="h-40 w-52 object-contain"
                                        />
                                        <span className="absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition group-hover:bg-black/40 group-hover:opacity-100">
                                            <ZoomIn />
                                        </span>
                                    </button>
                                ) : (
                                    <a
                                        href={`/submission-attachments/${attachment.id}/download`}
                                        className="flex h-40 w-52 flex-col items-center justify-center gap-2 rounded-md border bg-muted p-3 text-center text-xs"
                                    >
                                        <FileText className="size-8" />
                                        {attachment.original_name}
                                    </a>
                                )}
                                <span className="max-w-52 text-center text-xs break-all">
                                    {attachment.original_name}
                                </span>
                                {editable && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.delete(
                                                `/submission-attachments/${attachment.id}`,
                                            )
                                        }
                                    >
                                        Hapus
                                    </Button>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
            <Dialog
                open={Boolean(preview)}
                onOpenChange={(open) => !open && setPreview(null)}
            >
                <DialogContent className="max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>{preview?.original_name}</DialogTitle>
                    </DialogHeader>
                    {preview && (
                        <img
                            src={`/submission-attachments/${preview.id}/preview`}
                            alt={preview.original_name}
                            className="max-h-[80vh] w-full object-contain"
                        />
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}
