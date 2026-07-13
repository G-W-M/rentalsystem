{{--
    resources/views/exports/_pdf_styles.blade.php
    Shared inline CSS included at the top of every PDF export template.
    DomPDF does not support external stylesheets so everything must be inline.
--}}
<style>
    /* ===== Reset ===== */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #17202a;
        background: #fff;
    }

    /* ===== Page header ===== */
    .pdf-header {
        width: 100%;
        border-bottom: 3px solid #9BE866;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }

    .pdf-header table {
        width: 100%;
        border-collapse: collapse;
    }

    .pdf-header td {
        border: none;
        padding: 0;
        background: transparent;
        vertical-align: middle;
    }

    .logo-cell {
        width: 64px;
    }

    .logo-circle {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        border: 3px solid #9BE866;
        overflow: hidden;
        background: #FEFDD6;
    }

    .logo-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .brand-cell {
        padding-left: 12px !important;
    }

    .brand-name {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #504E76;
        font-weight: bold;
    }

    .report-title {
        font-size: 20px;
        font-weight: bold;
        color: #504E76;
        line-height: 1.2;
    }

    .meta-cell {
        text-align: right;
        color: #6c757d;
        font-size: 9px;
        vertical-align: bottom !important;
        padding-bottom: 2px !important;
    }

    /* ===== Filters bar ===== */
    .filters-bar {
        background: #f0fdf4;
        border-left: 4px solid #9BE866;
        padding: 6px 10px;
        margin-bottom: 14px;
        font-size: 9px;
        color: #504E76;
    }

    /* ===== Table ===== */
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }

    table.data-table th {
        background: #504E76;
        color: #ffffff;
        padding: 7px 8px;
        text-align: left;
        font-size: 9px;
        font-weight: bold;
        letter-spacing: 0.03em;
    }

    table.data-table td {
        padding: 6px 8px;
        border-bottom: 1px solid #e9ecef;
        font-size: 10px;
        vertical-align: top;
    }

    table.data-table tr:nth-child(even) td {
        background: #f8f9fa;
    }

    table.data-table tr:last-child td {
        border-bottom: none;
    }

    /* ===== Status / priority colours ===== */
    .status-completed,
    .st-completed {
        color: #16a34a;
        font-weight: bold;
    }

    .status-pending,
    .st-pending {
        color: #b8860b;
        font-weight: bold;
    }

    .status-awaiting_verification {
        color: #0369a1;
        font-weight: bold;
    }

    .status-failed,
    .status-rejected,
    .st-rejected {
        color: #dc2626;
        font-weight: bold;
    }

    .priority-emergency {
        color: #dc2626;
        font-weight: bold;
    }

    .priority-high {
        color: #b8860b;
        font-weight: bold;
    }

    .priority-medium {
        color: #0369a1;
    }

    .priority-low {
        color: #16a34a;
    }

    .st-available {
        color: #16a34a;
        font-weight: bold;
    }

    .st-occupied {
        color: #0369a1;
        font-weight: bold;
    }

    .st-maintenance {
        color: #b8860b;
        font-weight: bold;
    }

    /* ===== Totals row ===== */
    .totals-bar {
        background: #FEFDD6;
        border: 1px solid #9BE866;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 10px;
        color: #504E76;
        font-weight: bold;
        margin-bottom: 20px;
    }

    /* ===== Footer ===== */
    .pdf-footer {
        border-top: 1px solid #e9ecef;
        padding-top: 8px;
        font-size: 8px;
        color: #9ca3af;
        text-align: center;
    }

    .empty-row td {
        text-align: center;
        color: #9ca3af;
        padding: 20px;
        font-style: italic;
    }
</style>
