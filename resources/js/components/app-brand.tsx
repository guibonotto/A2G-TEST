import { cn } from '@/lib/utils';

type Props = {
    /** Hide the "A2G TEST" wordmark and render the square only. */
    markOnly?: boolean;
    /** Tailwind size classes for the square, e.g. "size-9". */
    markClassName?: string;
    className?: string;
};

/**
 * The single brand mark for the whole product: a violet square with "A2G"
 * plus the wordmark. The landing page, the auth screens and the sidebar all
 * render this, so the user never meets a second mark while moving between
 * them.
 */
export default function AppBrand({
    markOnly = false,
    markClassName = 'size-9',
    className,
}: Props) {
    return (
        <span className={cn('flex items-center gap-3', className)}>
            <span
                className={cn(
                    'flex shrink-0 items-center justify-center rounded-lg bg-primary text-[0.7rem] font-bold text-primary-foreground',
                    markClassName,
                )}
                aria-hidden="true"
            >
                A2G
            </span>

            {markOnly ? (
                <span className="sr-only">A2G TEST</span>
            ) : (
                <span className="truncate text-lg font-semibold">A2G TEST</span>
            )}
        </span>
    );
}
