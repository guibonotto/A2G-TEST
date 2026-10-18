import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppBrand from '@/components/app-brand';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    /**
     * Log in and register render their own branded header, so the layout
     * only draws one when the page asked for a title. Without this the user
     * sees the mark twice on those two screens.
     */
    if (!title) {
        return <>{children}</>;
    }

    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col gap-6">
                        <div className="flex items-center justify-between gap-4">
                            <Link
                                href={home()}
                                aria-label="A2G TEST - Home"
                                className="rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <AppBrand />
                            </Link>

                            <Link
                                href={home()}
                                className="inline-flex items-center gap-1.5 text-sm text-muted-foreground underline-offset-4 transition-colors hover:text-foreground hover:underline"
                            >
                                <ArrowLeft
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Back to site
                            </Link>
                        </div>

                        <div className="space-y-2">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {title}
                            </h1>

                            {description && (
                                <p className="text-sm text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
