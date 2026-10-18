import { AlertTriangle } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

/**
 * Field-level error. The icon is there so the message does not rely on
 * colour alone to be noticed.
 */
export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            {...props}
            role="alert"
            className={cn(
                'flex items-start gap-1.5 text-sm text-destructive',
                className,
            )}
        >
            <AlertTriangle
                className="mt-0.5 size-4 shrink-0"
                aria-hidden="true"
            />
            <span>{message}</span>
        </p>
    ) : null;
}
