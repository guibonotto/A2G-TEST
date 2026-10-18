import AppBrand from '@/components/app-brand';

/**
 * Brand mark used in the sidebar header. Renders the same mark as the
 * landing and auth screens; the square alone stays recognizable when the
 * sidebar collapses to icons.
 */
export default function AppLogo() {
    return (
        <AppBrand
            markClassName="size-8 rounded-md"
            className="gap-2 [&>span:last-child]:text-sm"
        />
    );
}
