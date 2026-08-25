import { FileImage, FileText, Plus, X } from 'lucide-react';
import { useRef } from 'react';
import {
    Attachment,
    AttachmentAction,
    AttachmentActions,
    AttachmentContent,
    AttachmentDescription,
    AttachmentGroup,
    AttachmentMedia,
    AttachmentTitle,
} from '@/components/ui/attachment';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Props = {
    label: string;
    files?: File[];
    onFiles: (files: File[]) => void;
    accept?: string;
    description?: string;
    required?: boolean;
};

const fileKey = (file: File) =>
    `${file.name}-${file.size}-${file.lastModified}`;

const fileSize = (size: number) => {
    if (size < 1024 * 1024) return `${Math.max(1, Math.round(size / 1024))} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
};

export function MultipleFileInput({
    label,
    files = [],
    onFiles,
    accept,
    description,
    required = false,
}: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const addFiles = (selected: File[]) => {
        const existing = new Set(files.map(fileKey));
        onFiles([
            ...files,
            ...selected.filter((file) => !existing.has(fileKey(file))),
        ]);
    };

    return (
        <div className="space-y-2">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <Label>
                        {label}
                        {required ? ' *' : ''}
                    </Label>
                    <p className="text-xs text-muted-foreground">
                        {description ??
                            'Tambahkan file satu per satu atau beberapa sekaligus.'}
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => inputRef.current?.click()}
                >
                    <Plus className="size-4" />
                    Tambah Attachment
                </Button>
            </div>
            <input
                ref={inputRef}
                type="file"
                multiple
                accept={accept}
                className="sr-only"
                onChange={(event) => {
                    addFiles(Array.from(event.target.files ?? []));
                    event.target.value = '';
                }}
            />
            {files.length === 0 ? (
                <button
                    type="button"
                    className="flex min-h-20 w-full items-center justify-center rounded-md border border-dashed px-4 text-sm text-muted-foreground hover:bg-muted/40"
                    onClick={() => inputRef.current?.click()}
                >
                    Belum ada attachment. Klik untuk menambahkan file.
                </button>
            ) : (
                <div className="space-y-2">
                    <p className="text-xs font-medium">
                        {files.length} attachment siap diunggah
                    </p>
                    <AttachmentGroup className="flex-wrap overflow-visible">
                        {files.map((file, index) => (
                            <Attachment
                                key={`${fileKey(file)}-${index}`}
                                size="sm"
                                className="w-full sm:w-auto sm:max-w-80"
                            >
                                <AttachmentMedia>
                                    {file.type.startsWith('image/') ? (
                                        <FileImage />
                                    ) : (
                                        <FileText />
                                    )}
                                </AttachmentMedia>
                                <AttachmentContent>
                                    <AttachmentTitle>
                                        {file.name}
                                    </AttachmentTitle>
                                    <AttachmentDescription>
                                        {fileSize(file.size)}
                                    </AttachmentDescription>
                                </AttachmentContent>
                                <AttachmentActions>
                                    <AttachmentAction
                                        type="button"
                                        title={`Hapus ${file.name}`}
                                        aria-label={`Hapus ${file.name}`}
                                        onClick={() =>
                                            onFiles(
                                                files.filter(
                                                    (_, fileIndex) =>
                                                        fileIndex !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <X className="size-4" />
                                    </AttachmentAction>
                                </AttachmentActions>
                            </Attachment>
                        ))}
                    </AttachmentGroup>
                </div>
            )}
        </div>
    );
}
