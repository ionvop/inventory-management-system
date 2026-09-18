<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0 0 4px;
            font-size: 20px;
            color: #111827;
        }
        .header .meta {
            font-size: 11px;
            color: #6b7280;
        }
        .header .meta span { display: block; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #111827;
        }
        tr.low-stock td {
            background-color: #fef2f2;
            color: #991b1b;
        }
        .movement-in { color: #047857; font-weight: bold; }
        .movement-out { color: #b91c1c; font-weight: bold; }
        .movement-bid { color: #6d28d9; font-weight: bold; }
        .text-right { text-align: right; }
        .footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        .empty {
            text-align: center;
            color: #6b7280;
            padding: 24px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inventory Report</h1>
        <div class="meta">
            <span>Generated: {{ now()->timezone($timezone)->format('Y-m-d H:i') }}</span>
            @if (!empty($dateFrom) || !empty($dateTo))
                <span>Period: {{ $dateFrom ?? 'Beginning' }} &rarr; {{ $dateTo ?? 'Now' }}</span>
            @endif
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="empty">No inventory data available for the selected period.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Stock After</th>
                    <th class="text-right">Bid After</th>
                    <th>User</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @foreach ($item->transactions as $t)
                        <tr class="{{ $item->is_low_stock ? 'low-stock' : '' }}">
                            <td>{{ $item->name }}</td>
                            <td class="movement-{{ $t->movement }}">
                                {{ $t->movement === 'in' ? 'In' : ($t->movement === 'out' ? 'Out' : 'Set Bid') }}
                            </td>
                            <td class="text-right">
                                {{ $t->movement === 'in' ? '+' : ($t->movement === 'out' ? '-' : '') }}{{ $t->quantity }}{{ $item->unit ? ' '.$item->unit : '' }}
                            </td>
                            <td class="text-right">{{ $t->stock_after !== null ? $t->stock_after.($item->unit ? ' '.$item->unit : '') : '—' }}</td>
                            <td class="text-right">{{ $t->bid_after !== null ? $t->bid_after.($item->unit ? ' '.$item->unit : '') : '—' }}</td>
                            <td>{{ $t->user->username }}</td>
                            <td>{{ $t->posted_at->timezone($timezone)->format('M j, g:i A') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Inventory Report &mdash; {{ now()->timezone($timezone)->format('Y-m-d H:i') }}
    </div>
</body>
</html>
