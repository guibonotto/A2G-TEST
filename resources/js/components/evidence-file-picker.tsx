import { Paperclip, X } from 'lucide-react';
import type { ChangeEvent } from 'react';
import { useEffect, useMemo, useRef } from 'react';
import { Button } from '@/components/ui/button';

export const EVIDENCE_ACCEPT =
    'image/png,image/jpeg,image/gif,image/webp,application/pdf,text/plain';

export const EVIDENCE_MAX_FILES = 10;

type Props = {
    id: string;
    files: File[];
    onChange: (files: File[]) => void;
    /** Validation errors keyed by `evidences` (array level) or `evidences.{index}` (per file). */
    errors?: Record<string, string | string[] | undefined>;
    disabled?: boolean;
};

function isSameFile(a: File, b: File): boolean {
    return (
        a.name === b.name &&
        a.size === b.size &&
        a.lastModified === b.lastModified
    );
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(0)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function errorMessage(
    value: string | string[] | undefined,
): string | undefined {
    return Array.isArray(value) ? value[0] : value;
}

/**
 * File picker that accumulates selections instead of replacing them, so the
 * user can add evidence files one at a time or several at once.
 */
export default function EvidenceFilePicker({
    id,
    files,
    onChange,
    errors = {},
    disabled = false,
}: Props) {
    const inputRef = useRef<HTMLInputElement>(null);

    const previews = useMemo(
        () =>
            files.map((file) =>
                file.type.startsWith('image/')
                    ? URL.createObjectURL(file)
                    : null,
            ),
        [files],
    );

    useEffect(() => {
        return () => {
            previews.forEach((url) => {
                if (url) {
                    URL.revokeObjectURL(url);
                }
            });
        };
    }, [previews]);

    function addFiles(e: ChangeEvent<HTMLInputElement>) {
        const incoming = Array.from(e.target.files ?? []);
        const unique = incoming.filter(
            (file) => !files.some((existing) => isSameFile(existing, file)),
        );

        onChange([...files, ...unique]);

        // Clear the native input so the same file can be picked again after being removed.
        e.target.value = '';
    }

    function removeFile(index: number) {
        onChange(files.filter((_, i) => i !== index));
    }

    const limitReached = files.length >= EVIDENCE_MAX_FILES;
    const listError = errorMessage(errors.evidences);

    return (
        <div className="grid gap-2">
            <input
                ref={inputRef}
                id={id}
                type="file"
                multiple
                accept={EVIDENCE_ACCEPT}
                onChange={addFiles}
                disabled={disabled}
                className="sr-only"
            />

            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={disabled || limitReached}
                    onClick={() => inputRef.current?.click()}
                >
                    <Paperclip />{' '}
                    {files.length === 0 ? 'Choose files' : 'Add more files'}
                </Button>
                <span className="text-xs text-muted-foreground">
                    {files.length}/{EVIDENCE_MAX_FILES} files · 10MB each
                </span>
            </div>

            {listError && (
                <p className="text-sm text-destructive">{listError}</p>
            )}

            {files.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {files.map((file, index) => {
                        const fileError = errorMessage(
                            errors[`evidences.${index}`],
                        );

                        return (
                            <li
                                key={`${file.name}-${file.size}-${file.lastModified}`}
                                className="flex flex-col gap-1 rounded-md border p-2"
                            >
                                <div className="flex items-center gap-3">
                                    {previews[index] ? (
                                        <img
                                            src={previews[index]}
                                            alt={file.name}
                                            className="h-12 w-16 rounded border object-cover"
                                        />
                                    ) : (
                                        <span className="flex h-12 w-16 items-center justify-center rounded border bg-muted text-xs text-muted-foreground">
                                            {file.name
                                                .split('.')
                                                .pop()
                                                ?.toUpperCase()}
                                        </span>
                                    )}

                                    <div className="min-w-0 flex-1">
                                        <p
                                            className="truncate text-sm"
                                            title={file.name}
                                        >
                                            {file.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatFileSize(file.size)}
                                        </p>
                                    </div>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        disabled={disabled}
                                        onClick={() => removeFile(index)}
                                        aria-label={`Remove ${file.name}`}
                                    >
                                        <X />
                                    </Button>
                                </div>

                                {fileError && (
                                    <p className="text-xs text-destructive">
                                        {fileError}
                                    </p>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
