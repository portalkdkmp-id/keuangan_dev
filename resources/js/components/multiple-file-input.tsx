import { Paperclip } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    label: string;
    files?: File[];
    onFiles: (files: File[]) => void;
    accept?: string;
    description?: string;
    required?: boolean;
};

export function MultipleFileInput({
    label,
    files = [],
    onFiles,
    accept,
    description,
    required = false,
}: Props) {
    return (
        <div className="space-y-2">
            <Label>
                {label}
                {required ? ' *' : ''}
            </Label>
            <div className="space-y-3 rounded-md border border-dashed p-3">
                <div className="flex items-start gap-2 text-sm">
                    <Paperclip className="mt-0.5 size-4 shrink-0" />
                    <div>
                        <p className="font-medium">
                            Pilih satu atau beberapa file
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {description ??
                                'Anda dapat memilih beberapa attachment sekaligus.'}
                        </p>
                    </div>
                </div>
                <Input
                    type="file"
                    multiple
                    accept={accept}
                    onChange={(event) =>
                        onFiles(Array.from(event.target.files ?? []))
                    }
                />
                {files.length > 0 && (
                    <div className="space-y-1 text-xs">
                        <p className="font-medium">
                            {files.length} file dipilih
                        </p>
                        <ul className="max-h-24 space-y-1 overflow-y-auto text-muted-foreground">
                            {files.map((file, index) => (
                                <li
                                    key={`${file.name}-${index}`}
                                    className="truncate"
                                >
                                    {index + 1}. {file.name}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </div>
    );
}
