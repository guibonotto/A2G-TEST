import { Link } from '@inertiajs/react';
import { BarChart3 } from 'lucide-react';
import { create as createTestCase } from '@/routes/test-cases';

/**
 * One shared "no data" treatment for every dashboard panel. Before this the
 * same state showed up two different ways: some panels printed a sentence
 * while others drew an axis from 0 to 4 with nothing plotted, which reads
 * as if data existed.
 */
export function ChartEmptyState({
    message = 'No test cases yet',
    showAction = true,
}: {
    message?: string;
    showAction?: boolean;
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-2 py-12 text-center">
            <BarChart3
                className="size-6 text-muted-foreground"
                aria-hidden="true"
            />

            <p className="text-sm text-muted-foreground">{message}</p>

            {showAction && (
                <Link
                    href={createTestCase()}
                    className="text-sm font-medium text-primary underline-offset-4 hover:underline"
                >
                    Create test case
                </Link>
            )}
        </div>
    );
}
