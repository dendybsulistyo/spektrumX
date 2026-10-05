<style>
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) {
        --operator-navy: #172033;
        --operator-blue: #3156d3;
        --operator-border: #d9e1eb;
        --operator-muted: #6b7890;
        min-height: calc(100vh - 132px);
        margin: calc(var(--space-8) * -1);
        padding: 22px !important;
        background: #f2f5f8 !important;
        color: #273449;
        font-family: 'Figtree', system-ui, sans-serif;
        font-size: 13px;
    }

    .operator-page-title {
        display: flex;
        align-items: center;
        gap: 9px;
        color: #172033 !important;
        font-size: 18px !important;
        font-weight: 750 !important;
        letter-spacing: -.018em;
    }

    .operator-page-title::before {
        content: "";
        width: 4px;
        height: 19px;
        border-radius: 2px;
        background: #3156d3;
    }

    .operator-queue-viewport {
        height: calc(100dvh - 132px);
        min-height: 0 !important;
        overflow: hidden;
    }

    .operator-queue-shell,
    .operator-queue-workspace {
        height: 100%;
        min-height: 0;
    }

    .operator-queue-workspace {
        display: flex;
        flex-direction: column;
    }

    .operator-queue-controls {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-3);
    }

    .operator-queue-controls-start,
    .operator-group-toolbar,
    .operator-queue-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .operator-queue-controls-start {
        min-width: 0;
        gap: var(--space-4);
    }

    .operator-group-toolbar {
        gap: var(--space-2);
        padding-left: var(--space-4);
        border-left: 1px solid var(--operator-border);
    }

    .operator-group-toolbar-label {
        margin-right: var(--space-1);
        color: var(--operator-muted);
        font-family: var(--font-heading);
        font-size: 13px;
        font-weight: 600;
    }

    .operator-queue-actions {
        justify-content: flex-end;
        gap: 8px;
    }

    .operator-order-list {
        min-height: 0;
        flex: 1 1 auto;
        margin-top: var(--space-4);
        padding-right: 5px;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    .operator-group-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-3);
        margin: var(--space-6) 0 var(--space-3);
        padding: var(--space-3) var(--space-4);
        color: #ffffff;
        background: var(--operator-navy);
        border-left: 5px solid var(--operator-blue);
    }

    .operator-group-heading:first-child {
        margin-top: 0;
    }

    .operator-group-heading-title {
        font-family: var(--font-heading);
        font-size: 16px;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .operator-group-heading-count {
        font-size: 12px;
        white-space: nowrap;
        opacity: .8;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) > div {
        gap: 14px !important;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .seg-tab {
        min-height: 34px;
        padding: 7px 13px;
        background: #ffffff;
        color: #56647a;
        border-color: var(--operator-border);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .seg-tab:hover {
        background: #f8fafc;
        color: var(--operator-navy);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .seg-tab.active {
        background: var(--operator-navy);
        color: #ffffff;
        border-color: var(--operator-navy);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-card {
        position: relative;
        overflow: hidden;
        background: #ffffff !important;
        border: 1px solid var(--operator-border);
        border-left: 3px solid #33445f;
        border-radius: 9px;
        margin-bottom: 10px !important;
        box-shadow: 0 7px 20px rgba(23, 32, 51, .045);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-date-rail {
        position: absolute;
        inset: 0 auto 0 0;
        width: 58px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border-right: 1px solid var(--operator-border);
        color: #526078;
        font-family: var(--font-heading);
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-date-rail strong {
        color: var(--operator-navy);
        font-size: 19px;
        font-weight: 800;
        letter-spacing: -.04em;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-date-rail span,
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-date-rail small {
        margin-top: 3px;
        color: #738096;
        font-size: 9px;
        font-weight: 750;
        letter-spacing: .08em;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-card-head {
        margin-left: 58px;
        min-height: 46px;
        padding: 9px 14px;
        background: #f6f8fb;
        border-bottom-color: var(--operator-border);
        color: var(--operator-navy);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-summary {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: 5px;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-identity {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        font-family: var(--font-heading);
        font-weight: 700;
        font-size: 14px !important;
        letter-spacing: -.005em !important;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-customer-line {
        display: inline-flex;
        min-width: 0;
        align-items: center;
        gap: 7px;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-meta-date,
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-meta-customer,
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-meta-operator {
        font-size: 13px !important;
        line-height: 1.45 !important;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-meta-date {
        color: #617087;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-meta-customer {
        color: #415168;
        font-weight: 600;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-meta-operator {
        color: #6b7890;
        font-weight: 500;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-meta-divider,
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-meta-divider {
        display: inline-block;
        width: 1px;
        height: 14px;
        flex: 0 0 1px;
        background: #c7d1de;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-meta-divider {
        height: 18px;
        margin: 0 7px;
        vertical-align: middle;
        background: #d2dae5;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-meta-divider-small {
        height: 13px;
        margin: 0 6px;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row {
        margin-left: 58px;
        min-height: 50px;
        padding: 9px 14px;
        background: #ffffff;
        border-bottom-color: #e6ebf1;
        font-size: 13px;
        transition: background-color .15s ease;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row:hover {
        background: #f8fafc;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row > div:first-child span[style*="font-size: 14px"] {
        font-size: 12px !important;
        color: #607087 !important;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row > div:last-child {
        gap: 8px !important;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .progress-tag {
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        padding: 0 8px;
        color: #536178;
        background: #edf1f5;
        border: 1px solid #dce3eb;
        border-radius: 5px;
        font-size: 11px !important;
        font-weight: 700;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-input {
        height: 32px !important;
        min-height: 32px !important;
        padding: 0 8px !important;
        background: #ffffff;
        color: #0f172a;
        border-color: #cbd5e1;
        border-radius: 5px;
        font-size: 12px !important;
        line-height: normal !important;
        box-sizing: border-box !important;
        outline: none;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-input:focus {
        border-color: var(--operator-blue);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-input:disabled {
        background: #edf1f5;
        color: #69768a;
        border-color: #d6dee8;
        cursor: not-allowed;
        opacity: 1;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-btn {
        min-height: 32px;
        padding: 6px 10px;
        background: var(--operator-blue);
        color: #ffffff;
        border-color: var(--operator-blue);
        border-radius: 5px;
        box-shadow: 0 3px 8px rgba(49, 86, 211, .14);
        font-size: 12px !important;
        font-weight: 700;
        transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-btn:hover {
        background: #2748b8;
        border-color: #2748b8;
        box-shadow: 0 4px 10px rgba(49, 86, 211, .2);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-btn-ghost {
        background: #ffffff;
        color: #334155;
        border-color: #cbd5e1;
        box-shadow: none;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-btn-ghost:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #94a3b8;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-btn-danger {
        background: #ffffff;
        color: #b42318;
        border-color: #f3b7b2;
        box-shadow: none;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-btn-danger:hover {
        background: #fff5f4;
        color: #912018;
        border-color: #e98d86;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .blueprint {
        background-color: #ffffff;
        border-color: var(--operator-border);
        border-radius: 8px;
        box-shadow: 0 6px 18px rgba(23, 32, 51, .04);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .tag {
        border: 1px solid #d6dee9;
        border-radius: 5px;
        font-size: 10px;
        font-weight: 700;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .tag-outline {
        border-color: #bdcae2;
        background: #f5f7fb;
        color: #415168;
    }

    #industry-desain .revision-source-tag {
        display: inline-flex;
        min-height: 32px;
        align-items: center;
        padding: 0 9px;
        box-sizing: border-box;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) input[type="checkbox"] {
        width: 16px;
        height: 16px;
        border-color: #aeb9c8;
        border-radius: 3px;
    }

    @media (max-width: 768px) {
        .operator-queue-viewport {
            height: calc(100dvh - 116px);
        }

        .operator-queue-controls {
            align-items: flex-start;
        }

        .operator-group-toolbar {
            padding-left: 0;
            border-left: 0;
        }

        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) {
            margin: -16px;
            padding: 14px !important;
        }

        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-card-head,
        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row {
            padding: 9px 11px;
        }

        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-date-rail {
            width: 50px;
        }

        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-card-head,
        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row {
            margin-left: 50px;
        }

        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row > div:last-child {
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>
