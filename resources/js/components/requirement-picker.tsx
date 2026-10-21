import { ExternalLink, Search } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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

function coverageLabel(count: number): string {
    if (count === 0) {
        return 'No cases';
    }

    return count === 1 ? '1 case' : `${count} cases`;
}

/**
 * A searchable list of requirements. A plain dropdown with dozens of items
 * made the user scroll and read every entry to find one, and showed only the
 * key and title. Each item now tells how many test cases already cover it,
 * and the requirement under the pointer (or the selected one) is summarised
 * below the list, with a link to its Jira issue.
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
    const [hoveredId, setHoveredId] = useState<number | null>(null);

    const terms = normalize(query).split(/\s+/).filter(Boolean);
    const matches = requirements.filter((requirement) => {
        const haystack = normalize(
            `${requirement.code} ${requirement.title} ${requirement.description ?? ''}`,
        );

        return terms.every((term) => haystack.includes(term));
    });

    /** Hover previews without committing; otherwise show what is selected. */
    const previewed =
        requirements.find((requirement) => requirement.id === hoveredId) ??
        requirements.find((requirement) => String(requirement.id) === value);

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
                className="flex max-h-60 flex-col gap-0.5 overflow-y-auto rounded-md border p-1"
                onMouseLeave={() => setHoveredId(null)}
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
                                onMouseEnter={() =>
                                    setHoveredId(requirement.id)
                                }
                                onFocus={() => setHoveredId(requirement.id)}
                                className={cn(
                                    'flex items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm transition-colors',
                                    selected
                                        ? 'bg-primary/10 text-foreground ring-1 ring-primary/40'
                                        : 'hover:bg-muted',
                                )}
                            >
                                <span className="flex-1">
                                    <span className="font-mono text-xs text-muted-foreground">
                                        {requirement.code}
                                    </span>{' '}
                                    {requirement.title}
                                </span>
                                <Badge
                                    variant={
                                        requirement.test_cases_count === 0
                                            ? 'warning'
                                            : 'secondary'
                                    }
                                    title="Test cases already linked to this requirement"
                                >
                                    {coverageLabel(
                                        requirement.test_cases_count,
                                    )}
                                </Badge>
                            </button>
                        );
                    })
                )}
            </div>

            {previewed ? (
                <div
                    className="flex flex-col gap-1.5 rounded-md bg-muted/60 p-3 text-sm"
                    aria-live="polite"
                >
                    <div className="flex items-start justify-between gap-2">
                        <p className="font-medium">
                            <span className="font-mono text-xs text-muted-foreground">
                                {previewed.code}
                            </span>{' '}
                            {previewed.title}
                        </p>
                        {previewed.jira_url && (
                            <a
                                href={previewed.jira_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Open in Jira
                                <ExternalLink
                                    className="size-3"
                                    aria-hidden="true"
                                />
                            </a>
                        )}
                    </div>

                    <p className="line-clamp-4 text-muted-foreground">
                        {previewed.description ?? 'No description.'}
                    </p>

                    <p className="text-xs text-muted-foreground">
                        {previewed.test_cases_count === 0
                            ? 'Not covered by any test case yet.'
                            : `Already covered by ${coverageLabel(previewed.test_cases_count)}.`}
                    </p>
                </div>
            ) : (
                <p className="rounded-md bg-muted/60 p-3 text-xs text-muted-foreground">
                    Point at or select a requirement to see its description.
                </p>
            )}
        </div>
    );
}
