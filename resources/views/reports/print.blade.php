<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ str($report['report'])->headline() }} report</title>
    <style>
        body { font-family: Arial, sans-serif; color: #17202a; margin: 2rem; }
        h1 { margin-bottom: .25rem; } p { color: #566573; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; font-size: .85rem; }
        th, td { border: 1px solid #ccd1d1; padding: .5rem; text-align: left; vertical-align: top; }
        th { background: #e8f6f3; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <h1>{{ str($report['report'])->headline() }}</h1>
    <p>Generated {{ $report['generated_at'] }} · {{ $report['row_count'] }} rows</p>
    @if ($report['rows'] === [])
        <p>No records matched the selected filters.</p>
    @else
        <table>
            <thead><tr>@foreach(array_keys($report['rows'][0]) as $column)<th>{{ str($column)->headline() }}</th>@endforeach</tr></thead>
            <tbody>@foreach($report['rows'] as $row)<tr>@foreach($row as $value)<td>{{ is_scalar($value) || $value === null ? $value : json_encode($value) }}</td>@endforeach</tr>@endforeach</tbody>
        </table>
    @endif
</body>
</html>
