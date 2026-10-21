import { Search } from 'lucide-react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { RequirementOption } from '@/types/requirements';

/** Lowercase and strip accents, so "criacao" finds "Criação". */
function normalize(text: string): string {
    return text
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();
}

/**
 * A searchable list of requirements. A plain dropdown with dozens of items
 * made the user scroll and read every entry to find one.
 */
export function RequirementPicker({
    requirements,
    value,
    onChange,
}: {
    requirements: RequirementOption[];
    value: string;
    onChange: (requirementId: string) => void;
}) {
    const [query, setQuery] = useState('');

    const terms = normalize(query).split(/\s+/).filter(Boolean);
    const matches = requirements.filter((requirement) => {
        const haystack = normalize(`${requirement.code} ${requirement.title}`);

        return terms.every((term) => haystack.includes(term));
    });

    return (
        <div className="flex flex-col gap-2">
            <div className="relative">
                <Search
                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder="Type a key (e.g. AAAAAAA-14) or a word"
                    aria-label="Search requirements"
                    className="pl-9"
                    autoFocus
                />
            </div>

            <p className="text-xs text-muted-foreground">
                {matches.length} of {requirements.length} requirement(s)
            </p>

            <div
                role="listbox"
                aria-label="Requirements"
                className="flex max-h-72 flex-col gap-0.5 overflow-y-auto rounded-md border p-1"
            >
                {matches.length === 0 ? (
                    <p className="px-2 py-6 text-center text-sm text-muted-foreground">
                        No requirement matches &ldquo;{query}&rdquo;.
                    </p>
                ) : (
                    matches.map((requirement) => {
                        const selected = value === String(requirement.id);

                        return (
                            <button
                                key={requirement.id}
                                type="button"
                                role="option"
                                aria-selected={selected}
                                onClick={() => onChange(String(requirement.id))}
                                className={cn(
                                    'rounded-sm px-2 py-1.5 text-left text-sm transition-colors',
                                    selected
                                        ? 'bg-primary/10 text-foreground ring-1 ring-primary/40'
                                        : 'hover:bg-muted',
                                )}
                            >
                                <span className="font-mono text-xs text-muted-foreground">
                                    {requirement.code}
                                </span>{' '}
                                {requirement.title}
                            </button>
                        );
                    })
                )}
            </div>
        </div>
    );
}
