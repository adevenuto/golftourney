<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1b1d1a; margin: 0; }
        .header { border-bottom: 3px solid #b08d57; padding-bottom: 12px; margin-bottom: 18px; }
        .eyebrow { color: #8a6c3f; font-size: 9px; letter-spacing: 3px; text-transform: uppercase; margin: 0; }
        h1 { color: #14432f; font-size: 26px; margin: 4px 0 0; }
        .meta { color: #6b6b66; font-size: 10px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead th {
            background: #14432f; color: #faf7ef; text-align: left;
            padding: 7px 10px; font-size: 9px; letter-spacing: 1px; text-transform: uppercase;
        }
        thead th.num, tbody td.num { text-align: right; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e8e1d0; }
        tbody tr:nth-child(even) td { background: #f7f4ec; }
        .name { text-transform: capitalize; font-weight: bold; color: #14432f; }
        .handicap { font-weight: bold; }
        .footer { margin-top: 16px; color: #9a9a93; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p class="eyebrow">The Black League</p>
        <h1>Handicaps</h1>
        <p class="meta">
            {{ $golfers->count() }} golfers &middot; Generated {{ $generatedAt->format('F j, Y') }}
            @if (!empty($search))
                &middot; Matching &ldquo;{{ $search }}&rdquo;
            @endif
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Golfer</th>
                <th class="num">Handicap</th>
                <th class="num">Rounds</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($golfers as $golfer)
                <tr>
                    <td class="name">{{ $golfer->first_name }} {{ $golfer->last_name }}</td>
                    <td class="num handicap">{{ number_format((float) $golfer->handicap, 2) }}</td>
                    <td class="num">{{ $golfer->number_of_rounds }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">The Black League · Robert A. Black Golf Course</p>
</body>
</html>
