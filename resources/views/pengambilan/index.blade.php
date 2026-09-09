<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Stock Screener</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .container {
            max-width: 1500px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 25px;
        }

        .header h1 {
            margin-bottom: 5px;
        }

        .header p {
            margin-top: 0;
            color: #6b7280;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.06);
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #111827;
            color: white;
            padding: 14px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .stock-symbol {
            font-weight: bold;
        }

        .signal {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
        }

        .signal-watch {
            background: #dcfce7;
            color: #166534;
        }

        .signal-neutral {
            background: #fef3c7;
            color: #92400e;
        }

        .signal-avoid {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-backtest {
            border: 0;
            background: #111827;
            color: white;
            padding: 8px 12px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-backtest:hover {
            opacity: .85;
        }

        .no-data {
            color: #9ca3af;
        }

        /*
        |--------------------------------------------------------------------------
        | Modal
        |--------------------------------------------------------------------------
        */

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(17, 24, 39, .55);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal {
            background: white;
            width: 100%;
            max-width: 480px;
            border-radius: 14px;
            box-shadow: 0 25px 60px rgba(0,0,0,.25);
            overflow: hidden;
        }

        .modal-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .modal-close {
            border: 0;
            background: transparent;
            cursor: pointer;
            font-size: 28px;
            color: #6b7280;
        }

        .modal-body {
            padding: 24px;
        }

        .backtest-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .backtest-row:last-child {
            border-bottom: 0;
        }

        .backtest-label {
            color: #6b7280;
        }

        .backtest-value {
            font-weight: bold;
        }

        .divider {
            margin: 15px 0;
            border-top: 1px solid #e5e7eb;
        }

        .metric-good {
            color: #15803d;
        }

        .metric-warning {
            color: #b45309;
        }

        .metric-bad {
            color: #dc2626;
        }

        .modal-footer {
            padding: 16px 24px;
            background: #f9fafb;
            font-size: 12px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>IDX Stock Screener</h1>

        <p>
            Technical screening & historical strategy evaluation
        </p>
    </div>

    <div class="card">

        <div class="table-wrapper">

            <table>

                <thead>
                <tr>
                    <th>Stock</th>
                    <th>Date</th>
                    <th>RSI</th>
                    <th>MA20</th>
                    <th>MA50</th>
                    <th>Volume</th>
                    <th>Entry</th>
                    <th>SL</th>
                    <th>TP</th>
                    <th>R/R</th>
                    <th>Score</th>
                    <th>Signal</th>
                    <th>Backtest</th>
                </tr>
                </thead>

                <tbody>

                @forelse($results as $result)

                    @php
                        $backtest = $result->stock->backtestResult;

                        $signalClass = match($result->signal) {
                            'WATCH' => 'signal-watch',
                            'NEUTRAL' => 'signal-neutral',
                            default => 'signal-avoid',
                        };
                    @endphp

                    <tr>

                        <td class="stock-symbol">
                            {{ $result->stock->symbol }}
                        </td>

                        <td>
                            {{ $result->date->format('d M Y') }}
                        </td>

                        <td>
                            {{ number_format($result->rsi, 2) }}
                        </td>

                        <td>
                            {{ number_format($result->ma20, 0) }}
                        </td>

                        <td>
                            {{ number_format($result->ma50, 0) }}
                        </td>

                        <td>
                            {{ number_format($result->volume_ratio, 2) }}x
                        </td>

                        <td>
                            Rp{{ number_format($result->entry_price, 0, ',', '.') }}
                        </td>

                        <td>
                            Rp{{ number_format($result->stop_loss, 0, ',', '.') }}
                        </td>

                        <td>
                            Rp{{ number_format($result->take_profit, 0, ',', '.') }}
                        </td>

                        <td>
                            {{ number_format($result->risk_reward_ratio, 2) }}
                        </td>

                        <td>
                            <strong>
                                {{ $result->score }}
                            </strong>
                        </td>

                        <td>
                            <span class="signal {{ $signalClass }}">
                                {{ $result->signal }}
                            </span>
                        </td>

                        <td>

                            @if($backtest)

                                <button
                                    type="button"
                                    class="btn-backtest"
                                    onclick='openBacktestModal(@json([
                                        "symbol" => $result->stock->symbol,

                                        "historical_days" =>
                                            $backtest->historical_days,

                                        "total_signals" =>
                                            $backtest->total_signals,

                                        "closed_trades" =>
                                            $backtest->closed_trades,

                                        "wins" =>
                                            $backtest->wins,

                                        "losses" =>
                                            $backtest->losses,

                                        "open_trades" =>
                                            $backtest->open_trades,

                                        "win_rate" =>
                                            (float) $backtest->win_rate,

                                        "profit_factor" =>
                                            (float) $backtest->profit_factor,

                                        "expectancy" =>
                                            (float) $backtest->expectancy,
                                    ]))'
                                >
                                    Detail
                                </button>

                            @else

                                <span class="no-data">
                                    No data
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="13">
                            Belum ada hasil screening.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- MODAL BACKTEST --}}

<div
    id="backtestModal"
    class="modal-overlay"
>

    <div class="modal">

        <div class="modal-header">

            <h2 id="modalTitle">
                Backtest
            </h2>

            <button
                type="button"
                class="modal-close"
                onclick="closeBacktestModal()"
            >
                &times;
            </button>

        </div>


        <div class="modal-body">

            <div class="backtest-row">
                <span class="backtest-label">
                    Historical Days
                </span>

                <span
                    id="historicalDays"
                    class="backtest-value"
                ></span>
            </div>


            <div class="backtest-row">
                <span class="backtest-label">
                    Total Signals
                </span>

                <span
                    id="totalSignals"
                    class="backtest-value"
                ></span>
            </div>


            <div class="backtest-row">
                <span class="backtest-label">
                    Closed Trades
                </span>

                <span
                    id="closedTrades"
                    class="backtest-value"
                ></span>
            </div>


            <div class="divider"></div>


            <div class="backtest-row">
                <span class="backtest-label">
                    Wins
                </span>

                <span
                    id="wins"
                    class="backtest-value"
                ></span>
            </div>


            <div class="backtest-row">
                <span class="backtest-label">
                    Losses
                </span>

                <span
                    id="losses"
                    class="backtest-value"
                ></span>
            </div>


            <div class="backtest-row">
                <span class="backtest-label">
                    Open
                </span>

                <span
                    id="openTrades"
                    class="backtest-value"
                ></span>
            </div>


            <div class="divider"></div>


            <div class="backtest-row">
                <span class="backtest-label">
                    Win Rate
                </span>

                <span
                    id="winRate"
                    class="backtest-value"
                ></span>
            </div>


            <div class="backtest-row">
                <span class="backtest-label">
                    Profit Factor
                </span>

                <span
                    id="profitFactor"
                    class="backtest-value"
                ></span>
            </div>


            <div class="backtest-row">
                <span class="backtest-label">
                    Expectancy
                </span>

                <span
                    id="expectancy"
                    class="backtest-value"
                ></span>
            </div>

        </div>


        <div class="modal-footer">
            Backtest V1 — Entry close signal,
            Stop Loss 1%, Take Profit 2%.
        </div>

    </div>

</div>


<script>

    function metricClass(element, type, value)
    {
        element.classList.remove(
            'metric-good',
            'metric-warning',
            'metric-bad'
        );

        if (type === 'winRate') {

            if (value >= 45) {
                element.classList.add('metric-good');
            } else if (value >= 33.33) {
                element.classList.add('metric-warning');
            } else {
                element.classList.add('metric-bad');
            }

        }


        if (type === 'profitFactor') {

            if (value >= 1.5) {
                element.classList.add('metric-good');
            } else if (value >= 1) {
                element.classList.add('metric-warning');
            } else {
                element.classList.add('metric-bad');
            }

        }


        if (type === 'expectancy') {

            if (value > 0) {
                element.classList.add('metric-good');
            } else if (value === 0) {
                element.classList.add('metric-warning');
            } else {
                element.classList.add('metric-bad');
            }

        }
    }


    function openBacktestModal(data)
    {
        document.getElementById('modalTitle')
            .innerText = data.symbol + ' Backtest';


        document.getElementById('historicalDays')
            .innerText = data.historical_days;


        document.getElementById('totalSignals')
            .innerText = data.total_signals;


        document.getElementById('closedTrades')
            .innerText = data.closed_trades;


        document.getElementById('wins')
            .innerText = data.wins;


        document.getElementById('losses')
            .innerText = data.losses;


        document.getElementById('openTrades')
            .innerText = data.open_trades;


        const winRate =
            document.getElementById('winRate');

        const profitFactor =
            document.getElementById('profitFactor');

        const expectancy =
            document.getElementById('expectancy');


        winRate.innerText =
            Number(data.win_rate).toFixed(2) + '%';


        profitFactor.innerText =
            Number(data.profit_factor).toFixed(2);


        expectancy.innerText =
            Number(data.expectancy).toFixed(2)
            + '% / trade';


        metricClass(
            winRate,
            'winRate',
            Number(data.win_rate)
        );


        metricClass(
            profitFactor,
            'profitFactor',
            Number(data.profit_factor)
        );


        metricClass(
            expectancy,
            'expectancy',
            Number(data.expectancy)
        );


        document.getElementById('backtestModal')
            .style.display = 'flex';
    }


    function closeBacktestModal()
    {
        document.getElementById('backtestModal')
            .style.display = 'none';
    }


    /*
     * Klik area gelap untuk menutup modal.
     */
    document.getElementById('backtestModal')
        .addEventListener('click', function (event) {

            if (event.target === this) {
                closeBacktestModal();
            }

        });


    /*
     * ESC untuk menutup modal.
     */
    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeBacktestModal();
        }

    });

</script>

</body>
</html>