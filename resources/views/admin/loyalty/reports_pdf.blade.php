<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loyalty Program Reports</title>
    <style>
        @page { size: A4; margin: 25mm 20mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 4px 0;
            font-size: 11px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 18px 0 8px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }
        .stats-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .stats-grid td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .stat-label {
            font-weight: bold;
            width: 40%;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #ccc;
            padding: 4px 6px;
            text-align: left;
        }
        table.data-table th {
            background: #f1f1f1;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .small {
            font-size: 9px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Loyalty Program Reports</h1>
        <p><strong>Period:</strong>
            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
            –
            {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
        </p>
        <p class="small">Generated at {{ now()->format('d M Y H:i:s') }}</p>
    </div>

    {{-- Participation Overview --}}
    <div>
        <div class="section-title">Participation Overview</div>
        <table class="stats-grid">
            <tr>
                <td class="stat-label">Total Students</td>
                <td>{{ $participation['total_users'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="stat-label">Active Participants (in period)</td>
                <td>{{ $participation['active_users'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="stat-label">Total Points Awarded (in period)</td>
                <td>{{ $participation['total_points_awarded'] ?? 0 }}</td>
            </tr>
        </table>
    </div>

    {{-- Top Point Earners --}}
    @if(!empty($participation['top_earners']) && count($participation['top_earners']) > 0)
        <div>
            <div class="section-title">Top Point Earners</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 8%;">Rank</th>
                        <th style="width: 32%;">Student Name</th>
                        <th style="width: 40%;">Email</th>
                        <th class="text-right" style="width: 20%;">Total Points (in period)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($participation['top_earners'] as $index => $user)
                        <tr>
                            <td class="text-center">#{{ $index + 1 }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td class="text-right">{{ $user->total_points ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Rewards Statistics --}}
    <div>
        <div class="section-title">Rewards Statistics</div>
        <table class="stats-grid">
            <tr>
                <td class="stat-label">Total Rewards (all time)</td>
                <td>{{ $rewardsStats['total_rewards'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="stat-label">Active Rewards (all time)</td>
                <td>{{ $rewardsStats['active_rewards'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="stat-label">Total Redemptions (in period)</td>
                <td>{{ $rewardsStats['total_redemptions'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="stat-label">Pending Approvals (in period)</td>
                <td>{{ $rewardsStats['pending_redemptions'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="stat-label">Approved Redemptions (in period)</td>
                <td>{{ $rewardsStats['approved_redemptions'] ?? 0 }}</td>
            </tr>
        </table>
    </div>

    {{-- Most Popular Rewards --}}
    @if(!empty($rewardsStats['popular_rewards']) && count($rewardsStats['popular_rewards']) > 0)
        <div>
            <div class="section-title">Most Popular Rewards (in period)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reward Name</th>
                        <th class="text-right">Redemptions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rewardsStats['popular_rewards'] as $reward)
                        <tr>
                            <td>{{ $reward->name }}</td>
                            <td class="text-right">{{ $reward->redemption_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</body>
</html>


