{{-- =======================================================================
     NACOC FORENSIC • UNIFIED INSTITUTIONAL REPORT DESIGN SYSTEM
     Executive Grey Command Masthead, Tabular Monospace KPIs,
     Instrument-Grade Filter Console, and Emerald Green Action System.
     ======================================================================= --}}
<style>
    :root {
        --rep-grey-50: #f8fafc;
        --rep-grey-100: #f1f5f9;
        --rep-grey-200: #e2e8f0;
        --rep-grey-300: #cbd5e1;

        --rep-canvas: #f3f5f8;
        --rep-surface: #ffffff;
        --rep-surface-subtle: #f8fafc;
        --rep-surface-muted: #eef2f6;

        --rep-border: #e2e8f0;
        --rep-border-strong: #cbd5e1;

        --rep-ink-950: #090e17;
        --rep-ink-900: #0f172a;
        --rep-ink-700: #334155;
        --rep-ink-500: #64748b;
        --rep-ink-400: #94a3b8;

        --rep-emerald-600: #059669;
        --rep-emerald-700: #047857;
        --rep-emerald-50: #ecfdf5;

        --rep-shadow-card: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px -12px rgba(15, 23, 42, 0.07);
        --rep-shadow-elevated: 0 4px 12px -2px rgba(15, 23, 42, 0.06), 0 20px 36px -16px rgba(15, 23, 42, 0.10);
    }

    body {
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: var(--rep-canvas);
        color: var(--rep-ink-900);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
    }

    .font-serif-header {
        font-family: 'Cinzel', Georgia, serif;
        letter-spacing: 0.03em;
    }

    .font-mono-code {
        font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
    }

    .tnum, .font-mono-code {
        font-variant-numeric: tabular-nums;
    }

    /* Interactive Affordances & Focus Precision */
    a[href], button:not(:disabled), select:not(:disabled), [role="button"] {
        cursor: pointer;
    }
    button:disabled, select:disabled {
        cursor: not-allowed;
    }
    a:focus-visible,
    button:not(:disabled):focus-visible,
    input:focus-visible,
    select:focus-visible,
    textarea:focus-visible {
        outline: 2px solid var(--rep-emerald-600);
        outline-offset: 2px;
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #eef2f6;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Sidebar Safeguard (Desktop only; mobile uses off-canvas drawer) */
    @media (min-width: 1024px) {
        aside.w-64 {
            width: 16rem !important;
            min-width: 16rem !important;
            max-width: 16rem !important;
            display: flex !important;
            flex-direction: column !important;
            flex-shrink: 0 !important;
            position: relative !important;
            z-index: 30 !important;
            overflow-x: hidden !important;
        }
    }
    aside.w-64 svg.w-4\.5,
    aside.w-64 svg[class*="w-4.5"] {
        width: 1.125rem !important;
        height: 1.125rem !important;
        min-width: 1.125rem !important;
        min-height: 1.125rem !important;
    }

    /* Executive Grey Command Masthead */
    .rep-masthead {
        border-radius: 1.25rem;
        background: var(--rep-surface);
        border: 1px solid var(--rep-border);
        box-shadow: var(--rep-shadow-card);
        overflow: hidden;
    }

    .rep-masthead-hero {
        position: relative;
        background: linear-gradient(180deg, #f1f5f9 0%, #e7edf4 100%);
        color: #0f172a;
        border-bottom: 1px solid #d5dee8;
        overflow: hidden;
        isolation: isolate;
    }

    .rep-masthead-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(to right, rgba(15, 23, 42, 0.035) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(15, 23, 42, 0.035) 1px, transparent 1px);
        background-size: 28px 28px;
        mask-image: radial-gradient(ellipse 80% 90% at 85% 20%, #000 25%, transparent 100%);
        -webkit-mask-image: radial-gradient(ellipse 80% 90% at 85% 20%, #000 25%, transparent 100%);
        pointer-events: none;
        z-index: 0;
    }

    .rep-masthead-inner {
        position: relative;
        z-index: 2;
    }

    /* KPI Telemetry Strip & Tiles */
    .rep-kpi-grid {
        background: var(--rep-surface);
    }

    .rep-kpi-tile {
        position: relative;
        background: var(--rep-surface);
        transition: background-color 0.16s ease;
    }

    .rep-kpi-tile:hover {
        background: #f8fafc;
    }

    /* Surface Cards */
    .rep-surface-card {
        background: var(--rep-surface);
        border: 1px solid var(--rep-border);
        border-radius: 1.25rem;
        box-shadow: var(--rep-shadow-card);
    }

    /* Instrument-Grade Segmented Period Switcher */
    .rep-segmented {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem;
        border-radius: 0.75rem;
        background: #eef2f6;
        border: 1px solid rgba(203, 213, 225, 0.85);
    }

    /* Unified Green Button System */
    .rep-btn-green {
        background-color: #059669;
        color: #ffffff;
        border: 1px solid #047857;
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.18);
        transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
    }
    .rep-btn-green:hover:not(:disabled) {
        background-color: #047857;
        border-color: #065f46;
        color: #ffffff;
    }
    .rep-btn-green:active:not(:disabled) {
        transform: scale(0.99);
    }

    .rep-btn-green-soft {
        background-color: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .rep-btn-green-soft:hover:not(:disabled) {
        background-color: #059669;
        border-color: #047857;
        color: #ffffff;
    }

    /* Pagination Links Green Styling */
    .rep-pagination nav span[aria-current="page"] > span {
        background-color: #059669 !important;
        border-color: #047857 !important;
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .rep-pagination nav a {
        background-color: #ecfdf5 !important;
        border-color: #a7f3d0 !important;
        color: #065f46 !important;
        font-weight: 600 !important;
    }
    .rep-pagination nav a:hover {
        background-color: #059669 !important;
        border-color: #047857 !important;
        color: #ffffff !important;
    }

    /* Precision Form Inputs */
    .rep-input,
    .rep-select {
        background-color: #f8fafc;
        border: 1px solid #dbe2ea;
        border-radius: 0.75rem;
        color: #0f172a;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }
    .rep-input:hover:not(:disabled):not([readonly]),
    .rep-select:hover:not(:disabled) {
        border-color: #cbd5e1;
        background-color: #ffffff;
    }
    .rep-input:focus:not([readonly]),
    .rep-select:focus {
        background-color: #ffffff;
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
        outline: none;
    }

    /* High-Density Forensic Ledger Table */
    .rep-table thead tr {
        background: #f1f5f9;
        border-bottom: 1px solid #dbe2ea;
    }

    .rep-table thead th {
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #475569;
    }

    .rep-table tbody tr {
        transition: background-color 0.12s ease, box-shadow 0.12s ease;
    }

    .rep-table tbody tr:hover {
        background-color: #f8fafc;
        box-shadow: inset 3px 0 0 #059669;
    }

    /* Skeleton Shimmer */
    @keyframes repShimmerPulse {
        0%, 100% { opacity: 0.48; }
        50% { opacity: 0.95; }
    }
    .skeleton-pulse {
        animation: repShimmerPulse 1.35s ease-in-out infinite;
    }

    /* Print Media Safeguards */
    .print-only-header {
        display: none;
    }
    @media print {
        aside,
        header,
        #reportFilterConsole,
        #paginationFooter,
        #paginationSection,
        #printActionButtons,
        #dossierModal,
        #reportNotesModal,
        #pdfModal,
        #mugshotModal,
        .no-print {
            display: none !important;
        }
        body, .report-print-container {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .rep-masthead-hero {
            display: none !important;
        }
        .print-only-header {
            display: block !important;
        }
        .overflow-x-auto {
            overflow: visible !important;
        }
        table {
            width: 100% !important;
            font-size: 9.5pt !important;
            border-collapse: collapse !important;
        }
        tr {
            page-break-inside: avoid !important;
        }
    }
</style>
