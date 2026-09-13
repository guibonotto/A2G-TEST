/**
 * Brand mark used in the sidebar and header. Mirrors the wordmark on the
 * login/register pages; the square keeps the mark recognizable when the
 * sidebar is collapsed to icons.
 */
export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary font-mono text-[10px] font-bold tracking-tight text-sidebar-primary-foreground">
                A2G
            </div>
            <div className="ml-1 flex flex-1 items-center gap-2 font-mono text-sm font-bold tracking-wide">
                <span
                    className="size-1.5 animate-pulse rounded-full bg-info"
                    aria-hidden="true"
                />
                <span className="truncate">
                    A2G<span className="text-primary">&nbsp;TEST</span>
                    <span className="text-info" aria-hidden="true">
                        .
                    </span>
                </span>
            </div>
        </>
    );
}
