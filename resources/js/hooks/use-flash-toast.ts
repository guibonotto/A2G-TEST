import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

/**
 * Replay the request that reverts an action. When the server refuses it
 * (for instance, the previous assignee left the project), say so instead
 * of failing silently.
 */
function undo(request: NonNullable<FlashToast['undo']>): void {
    router.visit(request.url, {
        method: request.method,
        data: request.data as Record<string, string | number | null>,
        preserveScroll: true,
        onError: (errors) => {
            toast.error(
                Object.values(errors)[0] ?? 'This action could not be undone.',
            );
        },
    });
}

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            const request = data.undo;

            toast[data.type](
                data.message,
                request
                    ? {
                          action: {
                              label: 'Undo',
                              onClick: () => undo(request),
                          },
                      }
                    : undefined,
            );
        });
    }, []);
}
