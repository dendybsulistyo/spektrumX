<style>
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) {
        --operator-navy: #172033;
        --operator-blue: #2563eb;
        --operator-border: #d7dee8;
        --operator-muted: #64748b;
        min-height: calc(100vh - 132px);
        background: #eef2f6;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .seg-tab {
        min-height: 38px;
        padding: 8px 16px;
        background: #ffffff;
        color: #475569;
        border-color: var(--operator-border);
        font-size: 13px;
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
        overflow: hidden;
        background: #ffffff !important;
        border: 1px solid var(--operator-border);
        border-left: 3px solid #475569;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-card-head {
        min-height: 54px;
        padding: 11px 16px;
        background: #f8fafc;
        border-bottom-color: var(--operator-border);
        color: var(--operator-navy);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row {
        min-height: 62px;
        padding: 12px 16px;
        background: #ffffff;
        border-bottom-color: #e7ebf0;
        transition: background-color .15s ease;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row:hover {
        background: #fbfcfe;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .progress-tag {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 4px 9px;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 3px;
        font-size: 12px;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-input {
        min-height: 34px;
        padding: 6px 9px;
        background: #ffffff;
        color: #0f172a;
        border-color: #cbd5e1;
        border-radius: 3px;
        outline: none;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-input:focus {
        border-color: var(--operator-blue);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-btn {
        min-height: 34px;
        padding: 7px 12px;
        background: var(--operator-blue);
        color: #ffffff;
        border-color: var(--operator-blue);
        border-radius: 3px;
        box-shadow: 0 1px 1px rgba(15, 23, 42, .08);
        transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .in-btn:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        box-shadow: 0 2px 4px rgba(15, 23, 42, .12);
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
    }

    @media (max-width: 768px) {
        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) {
            margin: -16px;
            padding: 16px;
        }

        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .order-card-head,
        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row {
            padding: 11px 12px;
        }

        :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan) .item-row > div:last-child {
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>
