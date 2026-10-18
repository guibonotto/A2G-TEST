import { Check, Copy } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    /** Text written to the clipboard. */
    value: string;
    /** Button label before the click. Hidden when `iconOnly` is set. */
    label?: string;
    /** Label shown for two seconds after a successful copy. */
    copiedLabel?: string;
    /** Message announced in a toast once the value is on the clipboard. */
    toastMessage?: string;
    iconOnly?: boolean;
    variant?: 'default' | 'outline' | 'secondary' | 'ghost';
    size?: 'default' | 'sm' | 'lg' | 'icon';
    className?: string;
};

/**
 * Copy control that says what it does before the click and confirms right
 * where the user is looking afterwards, rather than relying on a bare icon.
 */
export default function CopyButton({
    value,
    label = 'Copy',
    copiedLabel = 'Copied',
    toastMessage,
    iconOnly = false,
    variant = 'outline',
    size = 'default',
    className,
}: Props) {
    const [copied, setCopied] = useState(false);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, []);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(value);
        } catch {
            toast.error('Could not copy. Select the text and copy manually.');

            return;
        }

        setCopied(true);

        if (toastMessage) {
            toast.success(toastMessage);
        }

        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        timeoutRef.current = setTimeout(() => setCopied(false), 2000);
    };

    return (
        <Button
            type="button"
            variant={variant}
            size={iconOnly ? 'icon' : size}
            onClick={copy}
            aria-label={iconOnly ? label : undefined}
            title={iconOnly ? label : undefined}
            className={cn(copied && 'text-success', className)}
        >
            {copied ? (
                <Check className="size-4" aria-hidden="true" />
            ) : (
                <Copy className="size-4" aria-hidden="true" />
            )}

            {!iconOnly && (copied ? copiedLabel : label)}
        </Button>
    );
}
