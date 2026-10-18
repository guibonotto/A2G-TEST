import { Check, Circle } from 'lucide-react';
import { cn } from '@/lib/utils';

export type PasswordRule = {
    label: string;
    test: (value: string) => boolean;
};

/**
 * Mirrors the production password policy configured in AppServiceProvider
 * (min 12, mixed case, numbers, symbols). Local environments are more
 * permissive, so showing the stricter set never lets a user through a rule
 * the server would reject.
 */
export const defaultPasswordRules: PasswordRule[] = [
    {
        label: 'At least 12 characters',
        test: (value) => value.length >= 12,
    },
    {
        label: 'An uppercase and a lowercase letter',
        test: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value),
    },
    {
        label: 'A number',
        test: (value) => /\d/.test(value),
    },
    {
        label: 'A symbol',
        test: (value) => /[^A-Za-z0-9]/.test(value),
    },
];

type Props = {
    value: string;
    rules?: PasswordRule[];
    className?: string;
    id?: string;
};

/**
 * Lists the password rules up front and ticks each one as it is met, so the
 * user learns the policy before submitting instead of from an error.
 */
export default function PasswordRequirements({
    value,
    rules = defaultPasswordRules,
    className,
    id,
}: Props) {
    return (
        <ul id={id} className={cn('flex flex-col gap-1.5', className)}>
            {rules.map((rule) => {
                const met = rule.test(value);

                return (
                    <li
                        key={rule.label}
                        className={cn(
                            'flex items-center gap-2 text-sm transition-colors',
                            met ? 'text-success' : 'text-muted-foreground',
                        )}
                    >
                        {met ? (
                            <Check
                                className="size-4 shrink-0"
                                aria-hidden="true"
                            />
                        ) : (
                            <Circle
                                className="size-4 shrink-0"
                                aria-hidden="true"
                            />
                        )}

                        <span>{rule.label}</span>

                        <span className="sr-only">
                            {met ? '(met)' : '(not met yet)'}
                        </span>
                    </li>
                );
            })}
        </ul>
    );
}
