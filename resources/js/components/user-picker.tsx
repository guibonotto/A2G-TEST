import { Search } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { AssignableUser } from '@/types/test-cases';

/** Below this many people, a search box is an extra step with no gain. */
const SEARCH_THRESHOLD = 7;

/**
 * Pick who is responsible for a test case. Each person shows their role and
 * email, so similar names and demo accounts can be told apart.
 */
export function UserPicker({
    users,
    value,
    unassignedValue,
    onChange,
}: {
    users: AssignableUser[];
    value: string;
    unassignedValue: string;
    onChange: (value: string) => void;
}) {
    const [query, setQuery] = useState('');
    const term = query.trim().toLowerCase();
    const matches = users.filter((user) =>
        `${user.name} ${user.email} ${user.role ?? ''}`
            .toLowerCase()
            .includes(term),
    );

    const option = (key: string, optionValue: string, content: ReactNode) => (
        <button
            key={key}
            type="button"
            role="option"
            aria-selected={value === optionValue}
            onClick={() => onChange(optionValue)}
            className={cn(
                'flex flex-col rounded-sm px-2 py-1.5 text-left text-sm transition-colors',
                value === optionValue
                    ? 'bg-primary/10 ring-1 ring-primary/40'
                    : 'hover:bg-muted',
            )}
        >
            {content}
        </button>
    );

    return (
        <div className="flex flex-col gap-2">
            {users.length > SEARCH_THRESHOLD && (
                <div className="relative">
                    <Search
                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search by name, email or role"
                        aria-label="Search people"
                        className="pl-9"
                        autoFocus
                    />
                </div>
            )}

            <div
                role="listbox"
                aria-label="People"
                className="flex max-h-72 flex-col gap-0.5 overflow-y-auto rounded-md border p-1"
            >
                {option(
                    'unassigned',
                    unassignedValue,
                    <span className="text-muted-foreground">Unassigned</span>,
                )}

                {matches.map((user) =>
                    option(
                        String(user.id),
                        String(user.id),
                        <>
                            <span>
                                {user.name}
                                {user.role && (
                                    <span className="text-muted-foreground">
                                        {' '}
                                        · {user.role}
                                    </span>
                                )}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {user.email}
                            </span>
                        </>,
                    ),
                )}

                {matches.length === 0 && (
                    <p className="px-2 py-4 text-center text-sm text-muted-foreground">
                        Nobody in this project matches &ldquo;{query}&rdquo;.
                    </p>
                )}
            </div>

            <p className="text-xs text-muted-foreground">
                Only members of this project are listed.
            </p>
        </div>
    );
}
