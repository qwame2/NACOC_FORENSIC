<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&display=swap');

/* ==========================================================================
   SCREEN RULES: Hide print-only components
   ========================================================================== */
.cover-page,
.print-continuation-header,
.print-footer-container {
    display: none !important;
}

/* ==========================================================================
   PRINT RULES: High-Caliber Executive Document Layout (A4 Portrait)
   ========================================================================== */
@media print {
    @page {
        size: A4 portrait;
        margin: 8mm 10mm 10mm 10mm;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        box-sizing: border-box !important;
    }

    html, body {
        background: #ffffff !important;
        color: #0f172a !important;
        font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        font-size: 8pt !important;
        line-height: 1.35 !important;
        width: 100% !important;
        height: auto !important;
        overflow: visible !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Hide all web screen navigation, filters, buttons, sidebars, modals */
    aside,
    header,
    #auditFiltersBar,
    #auditSummaryMetrics,
    .audit-actions-bar,
    .audit-pagination,
    button,
    .no-print,
    .audit-col-details,
    .col-unit,
    .col-action,
    .col-route,
    #auditInspectModal,
    #mobileNavPanel,
    #sidebarNotificationsBadge,
    nav,
    .fixed {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Reset layout wrappers to full printable canvas */
    body,
    main,
    .flex,
    .flex-col,
    .overflow-hidden,
    .overflow-y-auto,
    .overflow-x-auto,
    #staffAuditTableContainer {
        display: block !important;
        position: static !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        overflow: visible !important;
        height: auto !important;
    }

    /* ----------------------------------------------------------------------
       1. EXACT A4 EXECUTIVE COVER PAGE (PAGE 1)
       ---------------------------------------------------------------------- */
    .cover-page {
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        box-sizing: border-box !important;
        width: 100% !important;
        min-height: 275mm !important;
        max-height: 275mm !important;
        height: 275mm !important;
        padding: 6mm 6mm 4mm 6mm !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        position: relative !important;
        overflow: hidden !important;
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
    }

    .cover-watermark {
        position: absolute !important;
        inset: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        pointer-events: none !important;
        opacity: 0.055 !important;
        z-index: 0 !important;
    }

    .cover-content {
        position: relative !important;
        z-index: 10 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        height: 100% !important;
        min-height: 100% !important;
        width: 100% !important;
    }

    .font-cinzel {
        font-family: 'Cinzel', 'Times New Roman', Georgia, serif !important;
    }

    .font-mono-code {
        font-family: 'JetBrains Mono', Consolas, monospace !important;
    }

    /* ----------------------------------------------------------------------
       2. CONTINUATION RUNNING HEADER (PAGES 2+)
       ---------------------------------------------------------------------- */
    .print-continuation-header {
        display: block !important;
        margin-top: 2pt !important;
        margin-bottom: 8pt !important;
        padding-bottom: 6pt !important;
        border-bottom: 2px solid #0f172a !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* ----------------------------------------------------------------------
       3. MASTER AUDIT TABLE IN THE BODY (STRICTLY 3 COLUMNS)
       ---------------------------------------------------------------------- */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin-top: 4pt !important;
        margin-bottom: 8pt !important;
        table-layout: fixed !important;
    }

    thead {
        display: table-header-group !important;
    }

    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    thead tr {
        background-color: #0f172a !important;
        color: #ffffff !important;
    }

    thead th {
        color: #ffffff !important;
        background-color: #0f172a !important;
        font-size: 8pt !important;
        font-weight: 800 !important;
        letter-spacing: 0.06em !important;
        text-transform: uppercase !important;
        padding: 6pt 8pt !important;
        border: 1px solid #1e293b !important;
        vertical-align: middle !important;
    }

    tbody tr:nth-child(even) {
        background-color: #f8fafc !important;
    }

    tbody tr:nth-child(odd) {
        background-color: #ffffff !important;
    }

    tbody td {
        border: 1px solid #cbd5e1 !important;
        padding: 5.5pt 8pt !important;
        font-size: 7.5pt !important;
        color: #0f172a !important;
        vertical-align: top !important;
        overflow-wrap: break-word !important;
        word-break: normal !important;
    }

    /* Column Width Allocations: Exactly 3 Columns */
    .col-timestamp   { width: 18% !important; }
    .col-officer     { width: 24% !important; }
    .col-description { width: 58% !important; }
    .audit-col-details { display: none !important; width: 0 !important; }

    .audit-desc-text {
        font-size: 8pt !important;
        font-weight: 700 !important;
        color: #020617 !important;
        line-height: 1.4 !important;
        margin-bottom: 2pt !important;
    }

    .audit-context-tag {
        display: inline-block !important;
        font-size: 6.5pt !important;
        font-weight: 800 !important;
        border: 1px solid #64748b !important;
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        padding: 0.5pt 3pt !important;
        border-radius: 2pt !important;
        margin-right: 2pt !important;
        margin-top: 1.5pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* ----------------------------------------------------------------------
       4. SIGN-OFF & CERTIFICATION FOOTER AT END OF BODY
       ---------------------------------------------------------------------- */
    .print-footer-container {
        display: block !important;
        margin-top: 14pt !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .page-break-inside-avoid {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
