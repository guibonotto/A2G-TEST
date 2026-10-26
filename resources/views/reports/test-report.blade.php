<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- The browser suggests the page title as the PDF file name. --}}
    <title>{{ Str::beforeLast($fileName, '.') }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f6; color: #111827; font: 13px/1.45 "Segoe UI", Helvetica, Arial, sans-serif; }
        .page { max-width: 1180px; margin: 24px auto; padding: 32px; background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, .08); }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; max-width: 1180px; margin: 24px auto -8px; }
        .toolbar a, .toolbar button { font: inherit; padding: 8px 14px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; cursor: pointer; text-decoration: none; }
        .toolbar button { background: #1f3a5f; border-color: #1f3a5f; color: #fff; font-weight: 600; }
        header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; padding-bottom: 16px; border-bottom: 2px solid #1f3a5f; }
        h1 { margin: 0; font-size: 22px; }
        .muted { color: #6b7280; }
        .nowrap { white-space: nowrap; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 10px; margin: 20px 0 24px; }
        .stat { padding: 10px 12px; border: 1px solid #e5e7eb; border-left-width: 4px; border-radius: 8px; }
        .stat strong { display: block; font-size: 22px; line-height: 1.2; }
        .stat span { color: #4b5563; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th { background: #1f3a5f; color: #fff; text-align: left; font-weight: 600; font-size: 12px; padding: 8px; }
        td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tr { break-inside: avoid; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .passed { background: #d1fae5; color: #065f46; border-left-color: #10b981; }
        .failed { background: #fee2e2; color: #991b1b; border-left-color: #ef4444; }
        .blocked { background: #dbeafe; color: #1e40af; border-left-color: #3b82f6; }
        .pending { background: #fef3c7; color: #92400e; border-left-color: #f59e0b; }
        .not-executed { background: #f3f4f6; color: #374151; border-left-color: #9ca3af; }
        .stat.passed, .stat.failed, .stat.blocked, .stat.pending, .stat.not-executed { background: #fff; color: inherit; }
        a.defect { color: #1d4ed8; }
        .empty { padding: 24px; text-align: center; color: #6b7280; }
        footer { margin-top: 16px; font-size: 11px; color: #6b7280; }
        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .toolbar { display: none; }
            .page { margin: 0; padding: 0; max-width: none; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('dashboard') }}">Back to dashboard</a>
        <button type="button" onclick="window.print()">Save as PDF</button>
    </div>

    <main class="page">
        <header>
            <div>
                <h1>Test report</h1>
                <div class="muted">{{ $project->name }}</div>
            </div>
            <div class="muted">Generated at {{ $generatedAt->format('d/m/Y H:i') }}</div>
        </header>

        <section class="summary" aria-label="Summary">
            @foreach ($summary as $indicator => $total)
                <div class="stat {{ Str::slug($indicator) }}">
                    <strong>{{ $total }}</strong>
                    <span>{{ $indicator }}</span>
                </div>
            @endforeach
        </section>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Situation</th>
                    <th>Assignee</th>
                    <th>Requirements</th>
                    <th>Last result</th>
                    <th>Executed at</th>
                    <th>Executed by</th>
                    <th>Jira defect</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['title'] }} <span class="muted">(v{{ $row['version'] }})</span></td>
                        <td>{{ $row['situation'] ?? '—' }}</td>
                        <td>{{ $row['assignee'] }}</td>
                        <td>
                            @forelse (array_filter(explode(', ', $row['requirements'])) as $code)
                                <span class="nowrap">{{ $code }}</span>@if (! $loop->last), @endif
                            @empty
                                —
                            @endforelse
                        </td>
                        <td><span class="pill {{ $row['resultSlug'] }}">{{ $row['result'] }}</span></td>
                        <td class="nowrap">{{ $row['executedAt']?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $row['executedBy'] ?? '—' }}</td>
                        <td>
                            @if ($row['defectKey'])
                                @if ($row['defectUrl'])
                                    <a class="defect" href="{{ $row['defectUrl'] }}">{{ $row['defectKey'] }}</a>
                                @else
                                    {{ $row['defectKey'] }}
                                @endif
                                @if ($row['defectStatus'])
                                    <span class="muted">({{ $row['defectStatus'] }})</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty">This project has no test cases yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <footer>Defect statuses as last read from Jira. Archived test cases are not included.</footer>
    </main>
</body>
</html>
