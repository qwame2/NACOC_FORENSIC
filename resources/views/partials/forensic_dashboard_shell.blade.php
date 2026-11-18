@php
    $authUser = auth()->user();
    $cases = $cases ?? \App\Models\ForensicCase::allCases();
    $cfg = $dashboardConfig ?? [];

    $unitKey = $cfg['unitKey'] ?? 'forensic_head';
    $unitBadge = $cfg['unitBadge'] ?? 'FORENSIC DIRECTORATE';
    $unitSubtitle = $cfg['unitSubtitle'] ?? 'Forensic Directorate Supreme Command';
    $locationNoun = $cfg['locationNoun'] ?? 'Forensic Cases';
    $pageTitle = $cfg['pageTitle'] ?? 'NACOC FORENSIC | Forensic Directorate Command Dashboard';
    $homeUrl = $cfg['homeUrl'] ?? url('/Forensic_Head');
    $casesUrl = $cfg['casesUrl'] ?? url('/Forensic_Head/cases');
    $createUrl = $cfg['createUrl'] ?? null;
    $createLabel = $cfg['createLabel'] ?? 'New Case Entry';
    $reportUrl = $cfg['reportUrl'] ?? url('/Report');
    $reviewBaseUrl = $cfg['reviewBaseUrl'] ?? url('/cases');
    $isStaffShell = !empty($cfg['isStaff']);
    $notificationsUrl = $cfg['notificationsUrl'] ?? url('/Forensic_Head/notifications');
    $permissionsUrl = match ($unitKey) {
        'crime_scene_support' => url('/Crime_Scene_Support/permissions'),
        'lab_analysis'        => url('/Lab_Analysis/permissions'),
        'evidence_management' => url('/Evidence_Management/permissions'),
        'digital_forensics'   => url('/Digital_Forensics/permissions'),
        default               => url('/Forensic_Head/permissions'),
    };
    $approvalsUrl = match ($unitKey) {
        'crime_scene_support' => url('/Crime_Scene_Support/reviews-approvals'),
        'lab_analysis'        => url('/Lab_Analysis/reviews-approvals'),
        'evidence_management' => url('/Evidence_Management/reviews-approvals'),
        'digital_forensics'   => url('/Digital_Forensics/reviews-approvals'),
        default               => url('/Forensic_Head/reviews-approvals'),
    };
    $pendingApprovalsCount = (!$isStaffShell && $authUser && $authUser->is_head)
        ? \App\Models\StaffCaseSubmission::forUnit((string) ($cfg['userUnit'] ?? ''))->where('status', 'Pending Approval')->count()
        : 0;
    $tabs = $cfg['tabs'] ?? [];
    $defaultUnitFilter = $cfg['defaultUnitFilter'] ?? '';
    $vaultPendingBadge = $cfg['vaultPendingBadge'] ?? 0;

    $headName = $authUser->fullname ?? session('user_name') ?? ($cfg['defaultHeadName'] ?? 'Commanding Officer');
    $officerRole = ($authUser && $authUser->is_head)
        ? ($cfg['headRoleLabel'] ?? 'Unit Head')
        : ($cfg['officerRoleLabel'] ?? 'Commanding Officer');

    $totalCasesCount = $cases->count();
    $pendingCasesCount = $pendingCasesCount
        ?? $cases->filter(fn($c) => in_array(strtolower((string)($c->case_status ?? $c->status ?? '')), ['awaiting sign-off', 'pending approval', 'pending'], true))->count();
    $inProgressCasesCount = $cases->filter(fn($c) => in_array(strtolower((string)($c->case_status ?? $c->status ?? '')), ['in progress', 'under investigation', 'under multi-unit processing', 'active'], true))->count();
    $approvedCasesCount = $cases->filter(fn($c) => in_array(strtolower((string)($c->case_status ?? $c->status ?? '')), ['approved', 'completed', 'authorized'], true))->count();
    $totalExhibitsCount = \App\Models\Exhibit::count();
    $completionRatePct = $totalCasesCount > 0 ? (int) round(($approvedCasesCount / $totalCasesCount) * 100) : 0;

    $completedHours = [];
    $activeHours = [];
    foreach ($cases as $cItem) {
        if (!empty($cItem->created_at)) {
            $sAt = \Carbon\Carbon::parse($cItem->created_at);
            $isAppr = in_array(strtolower((string)($cItem->case_status ?? $cItem->status ?? '')), ['approved', 'completed', 'authorized'], true);
            $eAt = ($isAppr && !empty($cItem->updated_at)) ? \Carbon\Carbon::parse($cItem->updated_at) : now();
            $hrs = max(1, $sAt->diffInHours($eAt));
            if ($isAppr && !empty($cItem->updated_at)) {
                $completedHours[] = $hrs;
            } else {
                $activeHours[] = $hrs;
            }
        }
    }
    $turnaroundHoursSrc = !empty($completedHours) ? $completedHours : $activeHours;
    $avgTurnaroundHours = !empty($turnaroundHoursSrc) ? (array_sum($turnaroundHoursSrc) / count($turnaroundHoursSrc)) : null;
    if ($avgTurnaroundHours === null) {
        $avgTurnaroundValue = 'N/A';
        $avgTurnaroundUnit = '';
    } elseif ($avgTurnaroundHours < 24) {
        $rHrs = max(1, (int) round($avgTurnaroundHours));
        $avgTurnaroundValue = (string) $rHrs;
        $avgTurnaroundUnit = $rHrs === 1 ? 'hr' : 'hrs';
    } else {
        $rDays = max(1, (int) round($avgTurnaroundHours / 24));
        $avgTurnaroundValue = (string) $rDays;
        $avgTurnaroundUnit = $rDays === 1 ? 'day' : 'days';
    }
    $mapConfig = $mapConfig ?? app(\App\Services\CaseAnalyticsService::class)->buildLeafletMapPayload($cases);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $pageTitle }}</title>

  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
      @vite(['resources/css/app.css', 'resources/js/app.js'])
  @else
      <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
  @endif

  <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
  <script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
  <script src="{{ asset('js/forensic-leaflet-map.js') }}?v={{ file_exists(public_path('js/forensic-leaflet-map.js')) ? filemtime(public_path('js/forensic-leaflet-map.js')) : 1 }}" defer></script>
  <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}" defer></script>
  <script src="{{ asset('vendor/lucide.min.js') }}" defer></script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --navy-950: #060d1c;
      --navy-900: #0b1525;
      --navy-800: #122033;
      --navy-700: #1c2f47;

      --paper: #f6f7f9;
      --paper-2: #eceff3;
      --surface: #ffffff;
      --surface-2: #fbfcfd;
      --hairline: #e4e8ee;
      --hairline-2: #d6dce4;

      --ink-900: #0a0f18;
      --ink-800: #141c28;
      --ink-700: #2a3442;
      --ink-600: #475162;
      --ink-500: #667081;
      --ink-400: #8a93a1;

      --signal: #0b8f6f;
      --signal-700: #087a5e;
      --signal-100: #d9f2ea;
      --signal-50: #edf9f5;

      --alert: #c2410c;
      --alert-100: #ffedd5;
      --warn: #b45309;
      --warn-100: #fef3c7;
      --info: #1d4ed8;
      --info-100: #dbeafe;
      --danger: #b91c1c;
      --danger-100: #fee2e2;

      --e1: 0 1px 1px 0 rgba(10, 15, 24, .04), 0 0 0 1px rgba(10, 15, 24, .03);
      --e2: 0 2px 6px -1px rgba(10, 15, 24, .06), 0 0 0 1px rgba(10, 15, 24, .04);
      --e3: 0 8px 24px -8px rgba(10, 15, 24, .10), 0 0 0 1px rgba(10, 15, 24, .04);
      --e-hero: 0 32px 64px -24px rgba(6, 13, 28, .40);

      --r-xs: 6px;
      --r-sm: 8px;
      --r-md: 12px;
      --r-lg: 16px;
      --r-xl: 20px;
      --r-2xl: 24px;
      --r-pill: 999px;
    }

    * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    html, body { height: 100%; }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--paper);
      color: var(--ink-800);
      font-feature-settings: 'cv02','cv03','cv04','cv11';
    }
    .mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
    .tnum { font-variant-numeric: tabular-nums; }

    /* ============ APP SHELL ============ */
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }
    .app {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      min-height: 100dvh;
      width: 100%;
      background: var(--paper);
    }

    /* Desktop (>= 1024px): Fit the whole screen seamlessly as a unified command center */
    @media (min-width: 1024px) {
      body {
        overflow: hidden;
      }
      .app {
        height: 100vh;
        height: 100dvh;
        max-height: 100vh;
        max-height: 100dvh;
        overflow: hidden;
      }
      .topbar {
        flex-shrink: 0;
      }
      .unit-strip {
        flex-shrink: 0;
      }
      .map-hero {
        flex: 1 1 0;
        min-height: 0;
        height: 100%;
        padding: 12px 18px;
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(350px, 1fr);
        gap: 14px;
        align-items: stretch;
        overflow: hidden;
      }
      .map-shell {
        height: 100%;
        min-height: 0;
      }
      .map-side-rail {
        height: 100%;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 4px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
      }
      .map-side-rail::-webkit-scrollbar {
        width: 6px;
      }
      .map-side-rail::-webkit-scrollbar-track {
        background: transparent;
      }
      .map-side-rail::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 999px;
      }
    }

    /* Tablet and Mobile (< 1024px): Responsive fluid stacked view */
    @media (max-width: 1023px) {
      body {
        overflow-x: hidden;
        overflow-y: auto;
      }
      .app {
        height: auto;
        min-height: 100vh;
        min-height: 100dvh;
        overflow: visible;
      }
      .map-hero {
        height: auto;
        display: flex;
        flex-direction: column;
        padding: 12px 14px 20px;
        gap: 16px;
        overflow: visible;
      }
      .map-shell {
        width: 100%;
        height: 52vh;
        min-height: 380px;
        max-height: 580px;
      }
      .map-side-rail {
        width: 100%;
        height: auto;
        overflow: visible;
      }
    }

    /* Small phones (<= 640px) */
    @media (max-width: 640px) {
      .map-shell {
        min-height: 340px;
        height: 48vh;
        border-radius: var(--r-xl);
      }
    }

    /* ============ TOP BAR ============ */
    .topbar {
      background: var(--surface);
      border-bottom: 1px solid var(--hairline);
      position: sticky; top: 0; z-index: 40;
      box-shadow: 0 1px 0 var(--hairline);
    }
    .topbar-inner {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 20px;
      max-width: 100%;
    }
    @media (max-width: 768px) { .topbar-inner { padding: 8px 12px; gap: 8px; } }

    .brand {
      display: flex; align-items: center; gap: 10px;
      text-decoration: none;
      flex-shrink: 0;
    }
    .brand img {
      width: 36px; height: 36px; object-fit: contain;
      background: #fff; border-radius: 9px; padding: 3px;
      box-shadow: var(--e1);
    }
    .brand-text .b1 { font-weight: 700; font-size: 13px; color: var(--ink-900); letter-spacing: -.005em; line-height: 1.15; }
    .brand-text .b2 { font-size: 9.5px; color: var(--ink-400); letter-spacing: .14em; text-transform: uppercase; font-weight: 700; margin-top: 2px; }
    @media (max-width: 640px) { .brand-text { display: none; } }

    /* Mobile Menu Drawer & Toggle */
    .mobile-menu-toggle {
      display: none;
    }
    @media (max-width: 1023px) {
      .mobile-menu-toggle {
        display: inline-flex;
      }
    }
    .mobile-nav-panel {
      background: var(--surface);
      border-bottom: 1px solid var(--hairline);
      padding: 12px 16px 16px;
      box-shadow: var(--e3);
      animation: slideDown .18s ease-out;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .mobile-nav-links {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .mobile-nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-radius: var(--r-md);
      color: var(--ink-700);
      font-size: 13px;
      font-weight: 500;
      text-decoration: none;
      transition: background .14s, color .14s;
    }
    .mobile-nav-link:hover, .mobile-nav-link:active {
      background: var(--paper);
      color: var(--ink-900);
    }
    .mobile-nav-link.active {
      background: var(--signal-50);
      color: var(--signal-700);
      font-weight: 600;
    }
    .mobile-nav-link .nb {
      min-width: 18px; height: 18px; padding: 0 5px;
      display: inline-flex; align-items: center; justify-content: center;
      background: var(--alert); color: #fff;
      font-size: 9.5px; font-weight: 700; border-radius: var(--r-pill);
      margin-left: auto;
    }
    .mobile-search-wrap {
      margin-top: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--paper);
      border: 1px solid var(--hairline);
      border-radius: var(--r-md);
      padding: 0 12px;
      height: 38px;
    }
    .mobile-search-wrap input {
      flex: 1;
      border: 0;
      background: transparent;
      outline: none;
      font-size: 12.5px;
      color: var(--ink-800);
    }

    /* Center nav (desktop) */
    .nav {
      display: flex; align-items: center; gap: 4px;
      flex-shrink: 0;
    }
    @media (max-width: 1024px) { .nav { display: none; } }

    .nav-link {
      display: inline-flex; align-items: center; gap: 8px;
      height: 36px; padding: 0 12px;
      border-radius: var(--r-sm);
      color: var(--ink-600); font-size: 12.5px; font-weight: 500;
      text-decoration: none;
      transition: background .16s, color .16s;
      position: relative;
    }
    .nav-link:hover { background: var(--paper); color: var(--ink-900); }
    .nav-link.active { background: var(--signal-50); color: var(--signal-700); font-weight: 600; }
    .nav-link.active::after {
      content: ''; position: absolute; left: 12px; right: 12px; bottom: -13px;
      height: 2px; background: var(--signal); border-radius: 2px;
    }
    .nav-link .nb {
      min-width: 18px; height: 18px; padding: 0 5px;
      display: inline-flex; align-items: center; justify-content: center;
      background: var(--alert); color: #fff;
      font-size: 9.5px; font-weight: 700; border-radius: var(--r-pill);
    }

    .topbar-spacer { flex: 1; min-width: 8px; }

    .cmd-search {
      position: relative;
      width: 280px; max-width: 40vw;
    }
    .cmd-search input {
      width: 100%; height: 36px; padding: 0 12px 0 34px;
      border-radius: var(--r-md);
      background: var(--paper);
      border: 1px solid var(--hairline);
      color: var(--ink-800); font-size: 12px;
      transition: all .16s;
    }
    .cmd-search input::placeholder { color: var(--ink-400); }
    .cmd-search input:focus {
      outline: none; background: #fff; border-color: var(--signal);
      box-shadow: 0 0 0 3px rgba(11,143,111,.12);
    }
    .cmd-search .si { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--ink-400); pointer-events: none; }
    @media (max-width: 640px) { .cmd-search { display: none; } }

    .icon-btn {
      width: 36px; height: 36px; border-radius: var(--r-md);
      background: var(--surface); border: 1px solid var(--hairline);
      color: var(--ink-600);
      display: inline-flex; align-items: center; justify-content: center;
      transition: all .16s;
      position: relative;
      flex-shrink: 0;
    }
    .icon-btn:hover { background: var(--paper); color: var(--ink-900); border-color: var(--hairline-2); }
    .icon-btn .dot-indicator {
      position: absolute; top: 7px; right: 7px; width: 6px; height: 6px;
      border-radius: 50%; background: var(--alert);
      box-shadow: 0 0 0 2px var(--surface);
    }

    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; height: 36px; padding: 0 14px; border-radius: var(--r-md); font-size: 12px; font-weight: 600; border: 1px solid transparent; transition: all .16s; white-space: nowrap; text-decoration: none; }
    .btn-primary { background: var(--navy-900); color: #fff; border-color: var(--navy-900); box-shadow: 0 1px 0 rgba(255,255,255,.08) inset, var(--e1); }
    .btn-primary:hover { background: var(--navy-800); }
    .btn-signal { background: var(--signal); color: #fff; border-color: var(--signal-700); box-shadow: 0 1px 0 rgba(255,255,255,.2) inset, 0 6px 16px -8px rgba(11,143,111,.6); }
    .btn-signal:hover { background: var(--signal-700); }
    .btn-ghost { background: var(--surface); color: var(--ink-700); border-color: var(--hairline); }
    .btn-ghost:hover { background: var(--paper); border-color: var(--hairline-2); color: var(--ink-900); }

    .avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: linear-gradient(135deg, var(--signal), var(--signal-700));
      color: #fff; display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 11.5px;
      box-shadow: 0 0 0 2px rgba(11,143,111,.18);
      flex-shrink: 0; overflow: hidden;
      position: relative;
    }
    .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .avatar .status-dot {
      position: absolute; bottom: -1px; right: -1px; width: 10px; height: 10px;
      border-radius: 50%; background: var(--signal); border: 2px solid #fff;
    }

    /* Unit strip below topbar */
    .unit-strip {
      background: var(--surface-2);
      border-bottom: 1px solid var(--hairline);
      padding: 7px 24px;
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px; flex-wrap: wrap;
      font-size: 11px;
    }
    @media (max-width: 768px) { .unit-strip { padding: 7px 14px; } }
    .unit-strip .u-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 3px 9px; border-radius: var(--r-pill);
      background: var(--signal-50); color: var(--signal-700);
      font-family: 'JetBrains Mono', monospace;
      font-size: 9.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      border: 1px solid var(--signal-100);
    }
    .unit-strip .u-badge::before {
      content: ''; width: 5px; height: 5px; border-radius: 50%; background: var(--signal);
      animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
    .unit-strip .u-title { font-weight: 600; color: var(--ink-800); font-size: 11.5px; }
    .unit-strip .u-sync { color: var(--ink-500); font-weight: 500; font-size: 10.5px; }

    /* ============ MAP HERO ============ */
    .map-hero {
      width: 100%;
      padding: 16px 24px 0;
      display: grid;
      grid-template-columns: minmax(0, 1.75fr) minmax(350px, 1fr);
      gap: 16px;
      align-items: stretch;
    }
    @media (max-width: 1100px) { .map-hero { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .map-hero { padding: 12px 14px 0; } }

    .map-shell {
      position: relative;
      width: 100%;
      height: 100%;
      min-height: 540px;
      background: var(--navy-950);
      border-radius: var(--r-2xl);
      overflow: hidden;
      box-shadow: var(--e-hero);
      isolation: isolate;
    }
    @media (max-width: 640px) { .map-shell { min-height: 440px; border-radius: var(--r-xl); } }

    .map-side-rail {
      display: flex;
      flex-direction: column;
      gap: 16px;
      min-width: 0;
    }

    .stats-split-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
      min-width: 0;
    }
    @media (max-width: 580px) {
      .stats-split-grid { grid-template-columns: 1fr; }
    }
    .stats-split-grid .card { min-width: 0; display: flex; flex-direction: column; }
    .stats-split-grid .card-head { padding: 12px 14px 8px !important; }
    .stats-split-grid .stats-body { padding: 12px 14px 14px; flex: 1; display: flex; flex-direction: column; }
    .stats-split-grid .stats-lollipop-chart { flex: 1; justify-content: space-between; }
    .stats-split-grid .stats-lollipop-row {
      grid-template-columns: 54px 1fr 26px;
      gap: 4px;
      height: 24px;
    }
    .stats-split-grid .stats-lollipop-grid { left: 58px; right: 30px; }
    .stats-split-grid .stats-lollipop-lbl { font-size: 10.5px; }
    .stats-split-grid .stats-lollipop-lane { height: 24px; }
    .stats-split-grid .stats-lollipop-head { width: 20px; height: 20px; font-size: 9.5px; }
    .stats-split-grid .stats-lollipop-pct { font-size: 9.5px; }

    .bar-chart-wrap {
      position: relative;
      width: 100%;
      height: 175px;
      padding: 10px 18px 14px;
    }

    .radial-card-body {
      padding: 16px 20px 18px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .radial-hero-num {
      font-size: 28px;
      font-weight: 700;
      letter-spacing: -.03em;
      color: var(--ink-900);
      line-height: 1.05;
      font-variant-numeric: tabular-nums;
      display: flex;
      align-items: baseline;
      gap: 6px;
    }
    .radial-hero-num span.sub {
      font-size: 13px;
      font-weight: 600;
      color: var(--ink-500);
      letter-spacing: 0;
    }
    .radial-layout {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding-top: 4px;
    }
    @media (max-width: 580px) {
      .radial-layout {
        flex-direction: column;
        align-items: center;
        gap: 16px;
      }
      .radial-svg-wrap {
        width: 160px;
        height: 160px;
      }
      .radial-legend {
        width: 100%;
        min-width: 0;
      }
    }
    .radial-svg-wrap {
      position: relative;
      width: 172px;
      height: 172px;
      flex-shrink: 0;
    }
    .radial-svg-wrap svg {
      width: 172px;
      height: 172px;
      transform: rotate(-90deg);
      display: block;
    }
    .radial-center {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      pointer-events: none;
      padding: 0 26px;
    }
    .radial-center .rc-val {
      font-size: 24px;
      font-weight: 700;
      color: var(--ink-900);
      letter-spacing: -.03em;
      line-height: 1;
      font-variant-numeric: tabular-nums;
    }
    .radial-center .rc-lbl {
      font-size: 11px;
      font-weight: 600;
      color: var(--ink-700);
      margin-top: 4px;
    }
    .radial-center .rc-sub {
      font-size: 10px;
      font-weight: 500;
      color: var(--ink-400);
      margin-top: 2px;
      line-height: 1.25;
    }
    .radial-legend {
      flex: 1;
      min-width: 155px;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    .radial-leg-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      font-size: 11px;
    }
    .radial-leg-name {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      color: var(--ink-600);
      font-weight: 500;
    }
    .radial-leg-bar {
      width: 3px;
      height: 12px;
      border-radius: 99px;
      flex-shrink: 0;
    }
    .radial-leg-val {
      font-family: 'JetBrains Mono', monospace;
      font-weight: 700;
      color: var(--ink-900);
      font-size: 12px;
    }

    .map-canvas {
      position: absolute; inset: 0;
      background-color: #0d1a2b;
      background-image:
        radial-gradient(1200px 500px at 20% 0%, rgba(11,143,111,.14), transparent 60%),
        radial-gradient(900px 400px at 90% 100%, rgba(29,78,216,.12), transparent 60%),
        radial-gradient(rgba(255,255,255,.06) 1px, transparent 1px);
      background-size: auto, auto, 24px 24px;
    }
    .map-canvas iframe, .map-canvas > div { position: absolute; inset: 0; }

    /* Top bar inside map */
    .map-top {
      position: relative; z-index: 10;
      padding: 16px 20px;
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 12px; flex-wrap: wrap;
    }
    @media (max-width: 640px) {
      .map-top {
        padding: 10px 12px;
        gap: 8px;
      }
      .map-title-block {
        max-width: 100%;
        padding: 5px 10px 5px 8px;
        gap: 8px;
      }
      .map-title-block .ttl { font-size: 12px; }
      .map-title-block .ttl-sub { display: none; }
      .map-filter-wrap {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
      }
      .map-filter {
        width: 100%;
        height: 32px;
        font-size: 11px;
        padding: 0 20px 0 8px;
      }
      .map-reset {
        height: 32px;
        width: 32px;
      }
    }
    @media (max-width: 380px) {
      .map-filter-wrap {
        grid-template-columns: 1fr;
      }
    }

    .map-title-block {
      display: inline-flex; align-items: center; gap: 10px;
      background: rgba(6, 13, 28, .55);
      backdrop-filter: blur(20px) saturate(1.4);
      -webkit-backdrop-filter: blur(20px) saturate(1.4);
      border: 1px solid rgba(255,255,255,.10);
      border-radius: var(--r-pill);
      padding: 7px 14px 7px 10px;
      color: #fff;
    }
    .map-title-block .map-pin-badge {
      width: 26px; height: 26px; border-radius: 50%;
      background: linear-gradient(135deg, var(--signal), var(--signal-700));
      display: inline-flex; align-items: center; justify-content: center;
      box-shadow: 0 0 0 3px rgba(11,143,111,.22);
    }
    .map-title-block .ttl { font-size: 13px; font-weight: 600; color: #fff; letter-spacing: .01em; }
    .map-title-block .ttl-sub { font-size: 10.5px; color: #94a3b8; font-weight: 500; margin-top: 1px; }
    .map-title-block .count-pill {
      display: inline-flex; align-items: center; justify-content: center;
      background: rgba(255,255,255,.14); color: #fff;
      height: 22px; padding: 0 9px; border-radius: var(--r-pill);
      font-size: 11px; font-weight: 700;
      font-family: 'JetBrains Mono', monospace;
      margin-left: 6px;
    }

    .map-filter-wrap {
      display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .map-filter {
      height: 36px; padding: 0 30px 0 12px;
      background: rgba(6, 13, 28, .6);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: var(--r-md);
      color: #dbe3ed; font-size: 11.5px; font-weight: 500;
      transition: all .16s;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 10px center;
    }
    .map-filter:hover { background-color: rgba(6, 13, 28, .8); border-color: rgba(255,255,255,.2); color: #fff; }
    .map-filter:focus { outline: none; border-color: var(--signal); box-shadow: 0 0 0 3px rgba(11,143,111,.25); }
    .map-filter option { background: var(--navy-900); color: #dbe3ed; }

    .map-reset {
      height: 36px; width: 36px; border-radius: var(--r-md);
      background: rgba(6, 13, 28, .6);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,.12);
      color: #dbe3ed; display: inline-flex; align-items: center; justify-content: center;
      transition: all .16s;
    }
    .map-reset:hover { background: rgba(6, 13, 28, .8); color: #fff; border-color: rgba(255,255,255,.2); }

    /* Selected docket dock */
    .map-dock {
      position: absolute; left: 20px; right: 20px; bottom: 20px;
      z-index: 10;
      display: flex; align-items: stretch; gap: 12px;
      max-width: 900px;
    }
    @media (max-width: 640px) {
      .map-dock { left: 8px; right: 8px; bottom: 8px; flex-direction: column; gap: 8px; }
      .dock-card { padding: 10px 12px; gap: 10px; }
      .dock-title { font-size: 13px; }
      .dock-sub { font-size: 10.5px; }
      .dock-meta .meta-val { font-size: 16px; }
      .dock-cta { min-height: 40px; justify-content: center; }
    }
      z-index: 10;
      display: flex; align-items: stretch; gap: 12px;
      max-width: 900px;
    }
    @media (max-width: 640px) {
      .map-dock { left: 12px; right: 12px; bottom: 12px; flex-direction: column; }
    }

    .dock-card {
      flex: 1;
      background: rgba(6, 13, 28, .74);
      backdrop-filter: blur(24px) saturate(1.5);
      -webkit-backdrop-filter: blur(24px) saturate(1.5);
      border: 1px solid rgba(255,255,255,.14);
      border-radius: var(--r-xl);
      padding: 14px 18px;
      display: flex; align-items: center; gap: 14px;
      color: #e5eaf1;
      min-width: 0;
    }
    .dock-priority {
      flex-shrink: 0;
      width: 44px; height: 44px; border-radius: var(--r-md);
      display: flex; align-items: center; justify-content: center;
      background: rgba(194, 65, 12, .18);
      border: 1px solid rgba(194, 65, 12, .35);
      color: #fb923c;
    }
    .dock-priority.low { background: rgba(11,143,111,.18); border-color: rgba(11,143,111,.4); color: #4dd4b0; }
    .dock-priority.med { background: rgba(180, 83, 9, .18); border-color: rgba(180, 83, 9, .4); color: #fbbf24; }

    .dock-content { min-width: 0; flex: 1; }
    .dock-status { font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #7ee0c2; }
    .dock-title { font-size: 15px; font-weight: 600; color: #fff; line-height: 1.25; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dock-sub { font-size: 11.5px; color: #94a3b8; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .dock-meta {
      flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 4px;
      padding-left: 14px; border-left: 1px solid rgba(255,255,255,.1);
    }
    .dock-meta .meta-val { font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 500; color: #fff; line-height: 1; }
    .dock-meta .meta-lbl { font-size: 10px; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; font-weight: 600; }

    .dock-cta {
      flex-shrink: 0; align-self: stretch;
      padding: 0 20px;
      border-radius: var(--r-xl);
      background: var(--signal);
      color: #fff; font-size: 12.5px; font-weight: 600;
      display: inline-flex; align-items: center; gap: 6px;
      border: 1px solid rgba(255,255,255,.15);
      box-shadow: 0 8px 20px -8px rgba(11,143,111,.7);
      transition: all .16s;
      text-decoration: none;
    }
    .dock-cta:hover { background: var(--signal-700); transform: translateY(-1px); }
    @media (max-width: 640px) { .dock-cta { min-height: 44px; justify-content: center; } }

    /* Map zoom stack */
    .map-zoom {
      position: absolute; right: 20px; bottom: 92px; z-index: 10;
      display: flex; flex-direction: column;
      background: rgba(6, 13, 28, .74);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: var(--r-md);
      overflow: hidden;
    }
    .map-zoom button {
      width: 38px; height: 38px; border: 0; background: transparent; color: #dbe3ed;
      display: flex; align-items: center; justify-content: center;
      transition: background .16s;
      border-bottom: 1px solid rgba(255,255,255,.06);
    }
    .map-zoom button:last-child { border-bottom: 0; }
    .map-zoom button:not(:disabled):hover { background: rgba(11,143,111,.25); color: #fff; }
    .map-zoom button:disabled { opacity: .35; }

    /* Map legend */
    .map-legend {
      position: absolute; left: 20px; bottom: 92px; z-index: 10;
      display: inline-flex; align-items: center; gap: 14px;
      background: rgba(6, 13, 28, .74);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: var(--r-md);
      padding: 8px 12px;
      font-size: 10.5px; color: #94a3b8;
    }
    .map-legend .sw { display: inline-flex; align-items: center; gap: 5px; }
    .map-legend .sw i { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
    @media (max-width: 640px) { .map-legend { display: none; } }

    /* ============ KPI STRIP ============ */
    .kpi-strip {
      padding: 16px 24px;
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 12px;
    }
    @media (max-width: 1100px) { .kpi-strip { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 640px) { .kpi-strip { grid-template-columns: repeat(2, 1fr); padding: 12px 14px; gap: 10px; } }

    .kpi {
      background: var(--surface);
      border: 1px solid var(--hairline);
      border-radius: var(--r-lg);
      padding: 14px 16px;
      display: flex; flex-direction: column; gap: 10px;
      box-shadow: var(--e1);
      transition: all .16s;
      text-align: left;
      text-decoration: none; color: inherit;
      cursor: default;
      font-family: inherit;
    }
    .kpi.clickable { cursor: pointer; }
    .kpi.clickable:hover { box-shadow: var(--e2); border-color: var(--hairline-2); transform: translateY(-1px); }

    .kpi-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .kpi-icon {
      width: 30px; height: 30px; border-radius: var(--r-sm);
      display: inline-flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .ic-warn { background: var(--warn-100); color: var(--warn); }
    .ic-signal { background: var(--signal-100); color: var(--signal-700); }
    .ic-info { background: var(--info-100); color: var(--info); }
    .ic-alert { background: var(--alert-100); color: var(--alert); }
    .ic-teal { background: #ccfbf1; color: #0f766e; }
    .ic-slate { background: var(--paper-2); color: var(--ink-700); }
    .kpi-spark { width: 64px; height: 22px; flex-shrink: 0; }

    .kpi-value {
      font-size: 26px; font-weight: 500; letter-spacing: -.02em;
      color: var(--ink-900); line-height: 1;
      display: flex; align-items: baseline; gap: 5px;
      font-variant-numeric: tabular-nums;
    }
    .kpi-value .u { font-size: 11px; font-weight: 600; color: var(--ink-500); letter-spacing: 0; }
    .kpi-label {
      font-size: 10.5px; font-weight: 600; color: var(--ink-500);
      text-transform: uppercase; letter-spacing: .08em;
    }

    /* ============ WORKSPACE ============ */
    .workspace {
      padding: 16px 24px 24px;
      display: grid;
      grid-template-columns: 1fr;
      gap: 16px;
      align-items: start;
    }
    .workspace:empty {
      display: none !important;
      padding: 0 !important;
      margin: 0 !important;
      height: 0 !important;
    }
    @media (max-width: 768px) { .workspace { padding: 14px; } }

    .card {
      background: var(--surface);
      border: 1px solid var(--hairline);
      border-radius: var(--r-2xl);
      box-shadow: var(--e2);
      display: flex; flex-direction: column;
      overflow: hidden;
    }

    .card-head {
      padding: 16px 20px 14px;
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px; flex-wrap: wrap;
      border-bottom: 1px solid var(--hairline);
    }
    .card-head h2 { font-size: 15px; font-weight: 700; color: var(--ink-900); letter-spacing: -.005em; }
    .card-head .hint { font-size: 11.5px; color: var(--ink-500); margin-top: 2px; }

    .count-chip {
      display: inline-flex; align-items: center; gap: 6px;
      height: 26px; padding: 0 10px;
      border-radius: var(--r-pill);
      background: var(--signal-50); color: var(--signal-700);
      font-size: 11px; font-weight: 700;
      font-family: 'JetBrains Mono', monospace;
      border: 1px solid var(--signal-100);
    }

    .toolbar {
      display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
      padding: 12px 20px;
      border-bottom: 1px solid var(--hairline);
      background: var(--surface-2);
    }
    .toolbar-label { font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--ink-400); }
    .sel {
      height: 34px; padding: 0 28px 0 10px;
      border-radius: var(--r-sm);
      background: var(--surface);
      border: 1px solid var(--hairline);
      color: var(--ink-700); font-size: 11.5px; font-weight: 500;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23667081' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 9px center;
      transition: all .16s;
    }
    .sel:hover { border-color: var(--hairline-2); }
    .sel:focus { outline: none; border-color: var(--signal); box-shadow: 0 0 0 3px rgba(11,143,111,.12); }

    /* Table */
    .tbl-wrap { overflow-x: auto; }
    table.dt { width: 100%; border-collapse: collapse; }
    table.dt thead th {
      padding: 10px 20px;
      text-align: left;
      font-size: 10px; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--ink-400);
      background: var(--surface-2);
      border-bottom: 1px solid var(--hairline);
      white-space: nowrap;
    }
    table.dt tbody tr { transition: background .14s; cursor: pointer; }
    table.dt tbody tr:hover { background: var(--surface-2); }
    table.dt tbody tr.selected { background: var(--signal-50); box-shadow: inset 3px 0 0 var(--signal); }
    table.dt tbody td {
      padding: 12px 20px;
      border-bottom: 1px solid var(--hairline);
      font-size: 12.5px; color: var(--ink-700);
      vertical-align: middle;
      white-space: nowrap;
    }
    table.dt tbody tr:last-child td { border-bottom: 0; }

    .cell-suspect { display: flex; align-items: center; gap: 10px; min-width: 200px; }
    .cell-suspect .id-badge {
      width: 28px; height: 28px; border-radius: var(--r-sm);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      background: var(--paper-2); color: var(--ink-700);
    }
    .cell-suspect .sname { font-weight: 600; color: var(--ink-900); overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
    .cell-suspect .scase { font-size: 10.5px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; margin-top: 1px; }

    .chip-status {
      display: inline-flex; align-items: center; gap: 6px;
      height: 22px; padding: 0 9px;
      border-radius: var(--r-pill);
      font-size: 10.5px; font-weight: 600;
      white-space: nowrap;
    }
    .chip-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .st-pending  { background: var(--warn-100);  color: #92400e; }
    .st-pending::before { background: var(--warn); }
    .st-active   { background: var(--info-100);  color: #1e40af; }
    .st-active::before  { background: var(--info); }
    .st-approved { background: var(--signal-100); color: var(--signal-700); }
    .st-approved::before { background: var(--signal); }

    .priority-mark { display: inline-flex; align-items: center; gap: 5px; font-weight: 600; font-size: 10.5px; letter-spacing: .02em; }
    .pr-critical { color: #b91c1c; } .pr-critical::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #dc2626; }
    .pr-high     { color: var(--alert); } .pr-high::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--alert); }
    .pr-medium   { color: var(--warn); } .pr-medium::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--warn); }
    .pr-low      { color: var(--signal-700); } .pr-low::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--signal); }

    .btn-row {
      display: inline-flex; align-items: center; gap: 4px;
      height: 28px; padding: 0 11px;
      border-radius: var(--r-sm);
      background: var(--paper); border: 1px solid var(--hairline);
      color: var(--ink-700); font-size: 11px; font-weight: 600;
      text-decoration: none; transition: all .16s;
    }
    .btn-row:hover { background: var(--navy-900); color: #fff; border-color: var(--navy-900); }

    .card-foot {
      padding: 12px 20px;
      display: flex; align-items: center; justify-content: space-between;
      gap: 10px; flex-wrap: wrap;
      border-top: 1px solid var(--hairline);
      background: var(--surface-2);
    }
    .card-foot .pag { font-size: 11px; color: var(--ink-500); font-weight: 500; }

    /* States */
    .state-block { padding: 48px 24px; text-align: center; }
    .state-block .icon {
      width: 44px; height: 44px; border-radius: var(--r-md);
      display: inline-flex; align-items: center; justify-content: center;
      margin-bottom: 12px;
    }
    .state-block h3 { font-size: 13.5px; font-weight: 600; color: var(--ink-900); margin-bottom: 4px; }
    .state-block p { font-size: 12px; color: var(--ink-500); margin-bottom: 14px; }

    /* Stats card */
    .stats-seg {
      display: flex; padding: 4px;
      background: var(--paper);
      border-radius: var(--r-md);
      margin: 14px 20px 0;
      gap: 3px;
    }
    .stats-seg button {
      flex: 1; height: 30px; padding: 0 8px;
      border-radius: var(--r-sm);
      border: 0; background: transparent;
      font-size: 11px; font-weight: 600; color: var(--ink-500);
      transition: all .16s;
    }
    .stats-seg button.active { background: var(--surface); color: var(--ink-900); box-shadow: var(--e1); }
    .stats-seg button:hover:not(.active) { color: var(--ink-700); }

    .stats-body {
      padding: 12px 18px 14px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .stats-lollipop-chart {
      position: relative;
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 4px 0 18px;
    }
    .stats-lollipop-grid {
      position: absolute;
      top: 0;
      bottom: 18px;
      left: 78px;
      right: 36px;
      pointer-events: none;
    }
    .stats-lollipop-tick {
      position: absolute;
      top: 0;
      bottom: 0;
      border-left: 1px dashed #e2e8f0;
    }
    .stats-lollipop-tick span {
      position: absolute;
      bottom: -16px;
      left: 0;
      transform: translateX(-50%);
      font-family: 'JetBrains Mono', monospace;
      font-size: 9px;
      font-weight: 600;
      color: #94a3b8;
    }
    .stats-lollipop-row {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: 74px 1fr 32px;
      align-items: center;
      gap: 6px;
      height: 28px;
    }
    .stats-lollipop-lbl {
      font-size: 11.5px;
      font-weight: 600;
      color: var(--ink-700);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .stats-lollipop-lane {
      position: relative;
      height: 28px;
      display: flex;
      align-items: center;
    }
    .stats-lollipop-stem {
      height: 3.5px;
      border-radius: 999px;
      transition: width .4s cubic-bezier(.22, 1, .36, 1);
    }
    .stats-lollipop-head {
      width: 24px;
      height: 24px;
      border-radius: 999px;
      color: #ffffff;
      font-size: 10.5px;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-left: -2px;
      flex-shrink: 0;
      box-shadow: 0 3px 8px rgba(15, 23, 42, .16);
      transition: transform .15s ease;
    }
    .stats-lollipop-row:hover .stats-lollipop-head {
      transform: scale(1.1);
    }
    .stats-lollipop-pct {
      font-family: 'JetBrains Mono', monospace;
      font-size: 10.5px;
      font-weight: 600;
      color: var(--ink-500);
      text-align: right;
    }
    .stats-tri { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .tri-item {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 9px 10px;
      min-width: 0;
    }
    .tri-item-top {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 4px;
    }
    .tri-item .tri-val {
      font-size: 19px; font-weight: 700; color: var(--ink-900); line-height: 1;
      font-variant-numeric: tabular-nums; letter-spacing: -.02em;
    }
    .tri-item .tri-pct {
      font-family: 'JetBrains Mono', monospace;
      font-size: 10px;
      font-weight: 600;
      color: var(--ink-500);
    }
    .tri-item .tri-lbl {
      display: flex; align-items: center; gap: 5px;
      font-size: 10px; font-weight: 600; color: var(--ink-600);
      text-transform: uppercase; letter-spacing: .04em;
      margin-top: 6px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .tri-item .tri-lbl i { width: 7px; height: 7px; border-radius: 999px; flex-shrink: 0; display: inline-block; }

    .stats-caption { font-size: 11px; color: var(--ink-500); line-height: 1.4; }

    .ops-list { border-top: 1px solid var(--hairline); padding: 14px 20px 16px; }
    .ops-list .ops-title { font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--ink-400); margin-bottom: 10px; }
    .ops-list .ops-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; font-size: 11.5px; }
    .ops-list .ops-row + .ops-row { border-top: 1px solid var(--hairline); }
    .ops-list .ops-lbl { display: inline-flex; align-items: center; gap: 7px; color: var(--ink-600); font-weight: 500; }
    .ops-list .ops-lbl i { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .ops-list .ops-val { font-weight: 600; color: var(--ink-900); font-family: 'JetBrains Mono', monospace; font-size: 11px; }

    /* Utilities */
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    a, button { -webkit-tap-highlight-color: transparent; }
    a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
      outline: 2px solid var(--signal); outline-offset: 2px;
    }
    @if(!empty($isStaffShell))
    /* Remove decorative status dots and blinking indicator dots on staff dashboards */
    .unit-strip .u-badge::before,
    .icon-btn .dot-indicator,
    .avatar .status-dot,
    .tri-item .tri-lbl i,
    .ops-list .ops-lbl i {
      display: none !important;
      animation: none !important;
    }
    @endif
  </style>
</head>
<body>

<div class="app">

  <!-- ====================================================== -->
  <!-- TOP BAR                                                -->
  <!-- ====================================================== -->
  <header class="topbar">
    <div class="topbar-inner">
      <a href="{{ $homeUrl }}" class="brand">
        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo">
        <div class="brand-text">
          <div class="b1">NACOC Forensic</div>
        </div>
      </a>

      <nav class="nav" aria-label="Primary navigation">
        <a href="{{ $homeUrl }}" class="nav-link active">
          <i data-lucide="layout-dashboard" style="width:15px;height:15px;"></i>
          <span>Dashboard</span>
        </a>
        <a href="{{ $casesUrl }}" class="nav-link">
          <i data-lucide="folder-search" style="width:15px;height:15px;"></i>
          <span>Case Register</span>
        </a>
        @if(!$isStaffShell && $unitKey === 'evidence_management')
        <a href="{{ url('/Evidence_Management/vault') }}" class="nav-link">
          <i data-lucide="archive" style="width:15px;height:15px;"></i>
          <span>Exhibit Vault</span>
          @if($vaultPendingBadge > 0)<span class="nb animate-pulse" style="background:#dc2626;color:#ffffff;min-width:18px;height:18px;padding:0 5px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-left:4px;">{{ $vaultPendingBadge }}</span>@endif
        </a>
        @endif
        @php
          $shellUnitContext = $cfg['userUnit'] ?? request()->path();
          $canShellGenerateReports = !$isStaffShell || !$authUser || $authUser->is_head || (method_exists($authUser, 'hasPermission') ? $authUser->hasPermission('generate_reports', $shellUnitContext) : true);
          $shellLockedReportTooltip = 'Locked: Generate Reports & PDFs permission is disabled by your Head of Unit';
        @endphp
        <a @if($canShellGenerateReports) href="{{ $reportUrl }}" @endif
           class="nav-link"
           @if($isStaffShell)
             data-staff-sitrep-btn="navbar"
             data-sitrep-url="{{ $reportUrl }}"
             aria-disabled="{{ $canShellGenerateReports ? 'false' : 'true' }}"
             @if(!$canShellGenerateReports)
               tabindex="0"
               title="{{ $shellLockedReportTooltip }}"
               data-locked-tooltip="{{ $shellLockedReportTooltip }}"
               onclick="event.preventDefault(); event.stopPropagation(); return false;"
               style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%); pointer-events: auto;"
             @endif
           @endif>
          <i data-lucide="bar-chart-3" style="width:15px;height:15px;"></i>
          <span>Reports</span>
          @if($isStaffShell)
            <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1 {{ $canShellGenerateReports ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          @endif
        </a>
        @if(!$isStaffShell && $authUser && $authUser->is_head)
          <a href="{{ $approvalsUrl }}" class="nav-link">
            <i data-lucide="clipboard-check" style="width:15px;height:15px;"></i>
            <span>Reviews &amp; Approvals</span>
            @if($pendingApprovalsCount > 0)
              <span class="nb animate-pulse" style="background:#dc2626;color:#ffffff;min-width:18px;height:18px;padding:0 5px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-left:4px;">{{ $pendingApprovalsCount }}</span>
            @endif
          </a>
          <a href="{{ $permissionsUrl }}" class="nav-link">
            <i data-lucide="shield-check" style="width:15px;height:15px;"></i>
            <span>Permissions</span>
          </a>
        @endif
        @if(!$isStaffShell && $notificationsUrl)
        <a href="{{ $notificationsUrl }}" class="nav-link">
          <i data-lucide="bell" style="width:15px;height:15px;"></i>
          <span>Notifications</span>
          @if($vaultPendingBadge > 0)<span class="nb">{{ $vaultPendingBadge }}</span>@endif
        </a>
        @endif
      </nav>

      <div class="topbar-spacer"></div>

      <div class="cmd-search">
        <i data-lucide="search" class="si" style="width:14px;height:14px;"></i>
        <input type="search" id="globalSearch" placeholder="Search cases, suspects, case IDs…" aria-label="Global search" oninput="applyDashboardFilters()">
      </div>

      <button type="button" class="icon-btn" onclick="refreshRealtimeData()" title="Sync telemetry" aria-label="Sync telemetry">
        <i data-lucide="refresh-cw" id="syncSpinner" style="width:15px;height:15px;"></i>
      </button>

      @if($createUrl)
        @php
          $shellUnitContext = $cfg['userUnit'] ?? request()->path();
          $canShellCreateCases = !$isStaffShell || !$authUser || $authUser->is_head || (method_exists($authUser, 'hasPermission') ? $authUser->hasPermission('create_cases', $shellUnitContext) : true);
          $shellLockedTooltip = 'Locked: Create New Cases permission is disabled by your Head of Unit';
        @endphp
        <a @if($canShellCreateCases) href="{{ $createUrl }}" @endif
           data-staff-create-btn="dashboard"
           data-create-url="{{ $createUrl }}"
           aria-disabled="{{ $canShellCreateCases ? 'false' : 'true' }}"
           @if(!$canShellCreateCases)
             tabindex="0"
             title="{{ $shellLockedTooltip }}"
             data-locked-tooltip="{{ $shellLockedTooltip }}"
             onclick="event.preventDefault(); event.stopPropagation(); return false;"
             style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%); background: #CBD5E1; color: #64748B; border-color: #CBD5E1;"
           @endif
           class="btn btn-signal">
          <i data-lucide="plus" style="width:14px;height:14px;"></i>
          <span style="display:none;" class="hidden sm:inline">{{ $createLabel }}</span>
          <span class="sm:hidden">New</span>
        </a>
      @endif

      <div class="avatar">
        @if ($authUser && $authUser->avatar)
          <img src="{{ asset($authUser->avatar) }}" alt="{{ $headName }}">
        @else
          {{ strtoupper(substr(trim($headName), 0, 2)) ?: 'NC' }}
        @endif
        @if(empty($isStaffShell))
          <span class="status-dot"></span>
        @endif
      </div>

      <form method="POST" action="{{ route('logout') }}" style="margin:0;">
        @csrf
        <button type="submit" class="icon-btn" title="Sign Out" aria-label="Sign Out">
          <i data-lucide="log-out" style="width:15px;height:15px;"></i>
        </button>
      </form>

      <button type="button" class="icon-btn mobile-menu-toggle" id="mobileMenuBtn" aria-label="Toggle navigation menu" onclick="toggleMobileNav()">
        <i data-lucide="menu" id="mobileMenuIcon" style="width:17px;height:17px;"></i>
      </button>
    </div>

    <!-- MOBILE NAVIGATION PANEL (< 1024px) -->
    <div class="mobile-nav-panel" id="mobileNavPanel" style="display:none;">
      <nav class="mobile-nav-links" aria-label="Mobile navigation">
        <a href="{{ $homeUrl }}" class="mobile-nav-link active">
          <i data-lucide="layout-dashboard" style="width:16px;height:16px;"></i>
          <span>Dashboard</span>
        </a>
        <a href="{{ $casesUrl }}" class="mobile-nav-link">
          <i data-lucide="folder-search" style="width:16px;height:16px;"></i>
          <span>Case Register</span>
        </a>
        @if(!$isStaffShell && $unitKey === 'evidence_management')
        <a href="{{ url('/Evidence_Management/vault') }}" class="mobile-nav-link">
          <i data-lucide="archive" style="width:16px;height:16px;"></i>
          <span>Exhibit Vault</span>
          @if($vaultPendingBadge > 0)<span class="nb animate-pulse" style="background:#dc2626;color:#ffffff;min-width:18px;height:18px;padding:0 5px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-left:4px;">{{ $vaultPendingBadge }}</span>@endif
        </a>
        @endif
        <a @if($canShellGenerateReports) href="{{ $reportUrl }}" @endif
           class="mobile-nav-link"
           @if($isStaffShell)
             data-staff-sitrep-btn="navbar-mobile"
             data-sitrep-url="{{ $reportUrl }}"
             aria-disabled="{{ $canShellGenerateReports ? 'false' : 'true' }}"
             @if(!$canShellGenerateReports)
               tabindex="0"
               title="{{ $shellLockedReportTooltip }}"
               data-locked-tooltip="{{ $shellLockedReportTooltip }}"
               onclick="event.preventDefault(); event.stopPropagation(); return false;"
               style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%); pointer-events: auto;"
             @endif
           @endif>
          <i data-lucide="bar-chart-3" style="width:16px;height:16px;"></i>
          <span>Reports</span>
          @if($isStaffShell)
            <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1 {{ $canShellGenerateReports ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          @endif
        </a>
        @if(!$isStaffShell && $authUser && $authUser->is_head)
          <a href="{{ $approvalsUrl }}" class="mobile-nav-link">
            <i data-lucide="clipboard-check" style="width:16px;height:16px;"></i>
            <span>Reviews &amp; Approvals</span>
            @if($pendingApprovalsCount > 0)
              <span class="nb animate-pulse" style="background:#dc2626;color:#ffffff;min-width:18px;height:18px;padding:0 5px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-left:4px;">{{ $pendingApprovalsCount }}</span>
            @endif
          </a>
          <a href="{{ $permissionsUrl }}" class="mobile-nav-link">
            <i data-lucide="shield-check" style="width:16px;height:16px;"></i>
            <span>Permissions</span>
          </a>
        @endif
        @if(!$isStaffShell && $notificationsUrl)
        <a href="{{ $notificationsUrl }}" class="mobile-nav-link">
          <i data-lucide="bell" style="width:16px;height:16px;"></i>
          <span>Notifications</span>
          @if($vaultPendingBadge > 0)<span class="nb">{{ $vaultPendingBadge }}</span>@endif
        </a>
        @endif
      </nav>
      <div class="mobile-search-wrap">
        <i data-lucide="search" style="width:14px;height:14px;color:var(--ink-400);"></i>
        <input type="search" placeholder="Search cases, suspects, case IDs…" aria-label="Mobile search" oninput="const s=document.getElementById('globalSearch'); if(s){ s.value=this.value; applyDashboardFilters(); }">
      </div>
    </div>
  </header>

  <!-- ====================================================== -->
  <!-- MAP HERO - full width, generous height                 -->
  <!-- ====================================================== -->
  <section class="map-hero" aria-label="Forensic cases geospatial view">
    <div class="map-shell">
      <div class="map-canvas">
        <x-leaflet-map
            id="forensicMapContainer"
            :latitude="$mapConfig['latitude']"
            :longitude="$mapConfig['longitude']"
            :zoom="$mapConfig['zoom']"
            :markers="$mapConfig['markers']"
            :tile-url="$mapConfig['tile_url']"
            :attribution="$mapConfig['attribution']"
            height="100%"
        />
      </div>

      <!-- Map top bar: title + filters -->
      <div class="map-top">
        <div class="map-title-block">
          <span class="map-pin-badge">
            <i data-lucide="map-pin" style="width:13px;height:13px;color:#fff;"></i>
          </span>
          <div>
            <div class="ttl">{{ $locationNoun === 'Cases' ? 'Cases' : $locationNoun . ' · Geospatial' }}</div>
            <div class="ttl-sub">Tracking {{ $totalCasesCount }} registered dockets across Ghana</div>
          </div>
          <span class="count-pill" id="mappedCaseCountPill">{{ $totalCasesCount }}</span>
        </div>

        <div class="map-filter-wrap">
          <label class="sr-only" for="filterPeriod">Period</label>
          <select id="filterPeriod" onchange="applyDashboardFilters()" class="map-filter">
            <option value="all">All periods</option>
            <option value="today">Today</option>
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
          </select>
          <label class="sr-only" for="filterRegion">Region</label>
          <select id="filterRegion" onchange="applyDashboardFilters()" class="map-filter">
            <option value="all">All regions</option>
            <option value="Greater Accra">Greater Accra</option>
            <option value="Ashanti">Ashanti</option>
            <option value="Western">Western</option>
            <option value="Eastern">Eastern</option>
            <option value="Central">Central</option>
            <option value="Volta">Volta</option>
            <option value="Northern">Northern</option>
            <option value="Bono">Bono</option>
            <option value="Upper East">Upper East</option>
            <option value="Upper West">Upper West</option>
          </select>
          <label class="sr-only" for="filterUnit">Unit</label>
          <select id="filterUnit" onchange="applyDashboardFilters()" class="map-filter">
            <option value="all">All units</option>
            <option value="Crime Scene Support">Crime Scene</option>
            <option value="Evidence Management">Evidence</option>
            <option value="Lab Analysis">Lab Analysis</option>
            <option value="Digital &amp; Physical Forensics">Digital Forensics</option>
          </select>
          <button type="button" class="map-reset" onclick="resetDashboardFilters()" title="Reset filters" aria-label="Reset filters">
            <i data-lucide="rotate-ccw" style="width:15px;height:15px;"></i>
          </button>
        </div>
      </div>

      <!-- Legend -->
      <div class="map-legend" aria-hidden="true">
        <span class="sw"><i style="background:#dc2626;"></i>High / Critical &amp; High-Case Area</span>
        <span class="sw"><i style="background:#0284c7;"></i>Medium</span>
        <span class="sw"><i style="background:#0f766e;"></i>Low</span>
      </div>

      <!-- Zoom controls -->
      <div class="map-zoom" title="Map controls">
        <button type="button" id="mapZoomInBtn" disabled onclick="window.ForensicMapStage.zoomIn()" title="Zoom in" aria-label="Zoom in">
          <i data-lucide="plus" style="width:15px;height:15px;"></i>
        </button>
        <button type="button" id="mapZoomOutBtn" disabled onclick="window.ForensicMapStage.zoomOut()" title="Zoom out" aria-label="Zoom out">
          <i data-lucide="minus" style="width:15px;height:15px;"></i>
        </button>
        <button type="button" id="mapResetViewBtn" disabled onclick="window.ForensicMapStage.resetView()" title="Reset view" aria-label="Reset view">
          <i data-lucide="maximize" style="width:15px;height:15px;"></i>
        </button>
      </div>

    </div>

    <!-- Right side of the map: Registered Dockets Bar Chart + Forensic Stats + Key Metrics -->
    <div class="map-side-rail">
      <!-- Bar Chart CardView: Registered Dockets -->
      <div class="card" aria-label="Registered dockets bar chart">
        <div class="card-head" style="padding:14px 18px 12px;">
          <div>
            <h2>Registered Dockets</h2>
            <div class="hint">Monthly docket intake &amp; exhibits overview</div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="count-chip" id="docketsBarBadge">{{ $totalCasesCount }} dockets</span>
          </div>
        </div>
        <div class="bar-chart-wrap">
          <canvas id="registeredDocketsBarChart" aria-label="Registered dockets bar chart"></canvas>
        </div>
      </div>

      <!-- Severity & Unit Side-by-Side CardViews -->
      <div class="stats-split-grid">
        <!-- Severity CardView -->
        <aside class="card" aria-label="Severity statistics">
          <div class="card-head" style="padding:14px 18px 10px;">
            <h2>Severity</h2>
            <span class="mono" style="font-size:11px;font-weight:600;color:var(--ink-500);" id="severityScopeCount">0 dockets</span>
          </div>
          <div class="stats-body">
            <div class="stats-lollipop-chart" id="forensicSeverityLollipop" role="img" aria-label="Severity lollipop chart"></div>
          </div>
        </aside>

        <!-- Unit CardView -->
        <aside class="card" aria-label="Unit statistics">
          <div class="card-head" style="padding:14px 18px 10px;">
            <h2>Unit</h2>
            <span class="mono" style="font-size:11px;font-weight:600;color:var(--ink-500);" id="unitScopeCount">0 dockets</span>
          </div>
          <div class="stats-body">
            <div class="stats-lollipop-chart" id="forensicUnitLollipop" role="img" aria-label="Unit lollipop chart"></div>
          </div>
        </aside>
      </div>

      <!-- Key Metrics Concentric Radial CardView -->
      <div class="card" aria-label="Key forensic metrics">
        <div class="card-head" style="padding:14px 20px 6px;border-bottom:0;">
          <div style="display:flex;align-items:center;gap:8px;">
            <i data-lucide="pie-chart" style="width:16px;height:16px;color:#3b82f6;"></i>
            <h2 style="font-size:15px;font-weight:600;">Key Metrics</h2>
          </div>
          <a href="{{ $casesUrl }}" class="icon-btn" style="width:30px;height:30px;border-radius:999px;" title="Open Full Directory" aria-label="Open Full Directory">
            <i data-lucide="more-horizontal" style="width:15px;height:15px;"></i>
          </a>
        </div>
        <div class="radial-card-body">
          <div class="radial-hero-num">
            <span id="kpiTotalCasesValue">{{ $totalCasesCount }}</span>
            <span class="sub">Dockets</span>
          </div>
          <div class="radial-layout">
            @php
              $officialStatuses = [
                  ['key' => 'under_investigation', 'label' => 'Under Investigation',        'color' => '#3b82f6'],
                  ['key' => 'on_remand',           'label' => 'On Remand',                  'color' => '#6366f1'],
                  ['key' => 'on_bail',             'label' => 'On Bail',                    'color' => '#0ea5e9'],
                  ['key' => 'in_court',            'label' => 'In Court',                   'color' => '#f59e0b'],
                  ['key' => 'verdict_imprisoned',  'label' => 'Verdict Passed - Imprisoned','color' => '#dc2626'],
                  ['key' => 'verdict_acquitted',   'label' => 'Verdict Passed - Acquitted', 'color' => '#10b981'],
                  ['key' => 'case_dismissed',      'label' => 'Case Dismissed',             'color' => '#64748b'],
                  ['key' => 'case_closed',         'label' => 'Case Closed',                'color' => '#0f766e'],
              ];
              $officialCounts = array_fill_keys(array_column($officialStatuses, 'key'), 0);

              foreach ($cases as $cItem) {
                  $stRaw = strtolower(trim((string) ($cItem->case_status ?? ($cItem->status ?? 'Under Investigation'))));
                  if (str_contains($stRaw, 'remand')) {
                      $officialCounts['on_remand']++;
                  } elseif (str_contains($stRaw, 'bail')) {
                      $officialCounts['on_bail']++;
                  } elseif (str_contains($stRaw, 'court')) {
                      $officialCounts['in_court']++;
                  } elseif (str_contains($stRaw, 'imprisoned')) {
                      $officialCounts['verdict_imprisoned']++;
                  } elseif (str_contains($stRaw, 'acquitted')) {
                      $officialCounts['verdict_acquitted']++;
                  } elseif (str_contains($stRaw, 'dismissed')) {
                      $officialCounts['case_dismissed']++;
                  } elseif (str_contains($stRaw, 'closed')) {
                      $officialCounts['case_closed']++;
                  } else {
                      $officialCounts['under_investigation']++;
                  }
              }

              $rankedStatuses = [];
              foreach ($officialStatuses as $stDef) {
                  $rankedStatuses[] = [
                      'key'   => $stDef['key'],
                      'label' => $stDef['label'],
                      'color' => $stDef['color'],
                      'count' => $officialCounts[$stDef['key']],
                  ];
              }
              usort($rankedStatuses, fn ($a, $b) => $b['count'] <=> $a['count']);

              $st1 = $rankedStatuses[0];
              $st2 = $rankedStatuses[1];
              $st3 = $rankedStatuses[2];

              $ringDenom = max(1, $totalCasesCount);
              $r1 = 76; $c1 = 2 * M_PI * $r1; $p1 = $st1['count'] > 0 ? min(1, max(0.06, $st1['count'] / $ringDenom)) : 0;
              $r2 = 64; $c2 = 2 * M_PI * $r2; $p2 = $st2['count'] > 0 ? min(1, max(0.06, $st2['count'] / $ringDenom)) : 0;
              $r3 = 52; $c3 = 2 * M_PI * $r3; $p3 = $st3['count'] > 0 ? min(1, max(0.06, $st3['count'] / $ringDenom)) : 0;
              $topPct = $totalCasesCount > 0 ? (int) round(($st1['count'] / $totalCasesCount) * 100) : 0;
            @endphp
            <div class="radial-svg-wrap">
              <svg viewBox="0 0 172 172" aria-label="Key metrics case status radial chart">
                <!-- Dashed circular tracks -->
                <circle cx="86" cy="86" r="{{ $r1 }}" fill="none" stroke="#cbd5e1" stroke-width="1.25" stroke-dasharray="3 4" />
                <circle cx="86" cy="86" r="{{ $r2 }}" fill="none" stroke="#cbd5e1" stroke-width="1.25" stroke-dasharray="3 4" />
                <circle cx="86" cy="86" r="{{ $r3 }}" fill="none" stroke="#cbd5e1" stroke-width="1.25" stroke-dasharray="3 4" />
                <!-- Active rounded-cap concentric rings -->
                <circle id="ringStatus1" cx="86" cy="86" r="{{ $r1 }}" fill="none" stroke="{{ $st1['color'] }}" stroke-width="7.5" stroke-linecap="round"
                        stroke-dasharray="{{ number_format($c1, 2, '.', '') }}"
                        stroke-dashoffset="{{ number_format($c1 * (1 - $p1), 2, '.', '') }}" />
                <circle id="ringStatus2" cx="86" cy="86" r="{{ $r2 }}" fill="none" stroke="{{ $st2['color'] }}" stroke-width="7.5" stroke-linecap="round"
                        stroke-dasharray="{{ number_format($c2, 2, '.', '') }}"
                        stroke-dashoffset="{{ number_format($c2 * (1 - $p2), 2, '.', '') }}" />
                <circle id="ringStatus3" cx="86" cy="86" r="{{ $r3 }}" fill="none" stroke="{{ $st3['color'] }}" stroke-width="7.5" stroke-linecap="round"
                        stroke-dasharray="{{ number_format($c3, 2, '.', '') }}"
                        stroke-dashoffset="{{ number_format($c3 * (1 - $p3), 2, '.', '') }}" />
              </svg>
              <div class="radial-center">
                <div class="rc-val"><span id="kpiTopStatusPct">{{ $topPct }}</span>%</div>
                <div class="rc-lbl" id="kpiTopStatusLabel">{{ $totalCasesCount > 0 ? $st1['label'] : 'No cases' }}</div>
                <div class="rc-sub"><span id="kpiTopStatusCount">{{ $st1['count'] }}</span> of {{ $totalCasesCount }} dockets</div>
              </div>
            </div>
            <div class="radial-legend">
              @foreach($officialStatuses as $stDef)
                <div class="radial-leg-item">
                  <span class="radial-leg-name"><i class="radial-leg-bar" style="background:{{ $stDef['color'] }};"></i>{{ $stDef['label'] }}</span>
                  <span class="radial-leg-val" id="kpiSt_{{ $stDef['key'] }}">{{ $officialCounts[$stDef['key']] }}</span>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ====================================================== -->
  <!-- WORKSPACE: table + breakdown                           -->
  <!-- ====================================================== -->
  <section class="workspace">

  </section>

</div>

<script>
  (function () {
    const REALTIME_ENDPOINT = @json(url('/api/dashboard/realtime'));
    const REVIEW_BASE_URL = @json($reviewBaseUrl);
    const DEFAULT_UNIT_FILTER = @json($defaultUnitFilter);

    let telemetryPayload = null;
    let lastTelemetryHash = '';
    let filteredCases = [];
    let selectedCaseId = null;
    let showAllRows = false;
    const sparkCharts = {};

    window.ForensicMapStage = {
      containerId: 'forensicMapContainer',
      instance: null, handlers: {},
      mount(instance, handlers = {}) {
        this.instance = instance; this.handlers = handlers || {};
        ['mapZoomInBtn','mapZoomOutBtn','mapResetViewBtn'].forEach((id) => {
          const b = document.getElementById(id); if (!b) return;
          b.disabled = false; b.removeAttribute('aria-disabled');
        });
      },
      getFilteredCases() { return filteredCases.slice(); },
      zoomIn() { this.handlers.zoomIn && this.handlers.zoomIn(); },
      zoomOut() { this.handlers.zoomOut && this.handlers.zoomOut(); },
      resetView() { this.handlers.resetView && this.handlers.resetView(); }
    };

    function icons() { if (window.lucide) window.lucide.createIcons(); }
    function esc(v) { const d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; }
    function normStatus(s) {
      s = String(s || '').toLowerCase();
      if (s.includes('pending') || s.includes('awaiting')) return 'pending';
      if (s.includes('approved') || s.includes('completed') || s.includes('authorized')) return 'approved';
      return 'in_progress';
    }
    function priorityClass(p) {
      p = String(p || 'HIGH').toUpperCase();
      if (p === 'CRITICAL') return 'pr-critical';
      if (p === 'HIGH') return 'pr-high';
      if (p === 'MEDIUM') return 'pr-medium';
      return 'pr-low';
    }
    function statusClass(s) {
      const b = normStatus(s);
      if (b === 'pending') return 'st-pending';
      if (b === 'approved') return 'st-approved';
      return 'st-active';
    }

    function sparkline(id, series, stroke, fill) {
      const c = document.getElementById(id);
      if (!c || typeof window.Chart === 'undefined') return;
      const pts = Array.isArray(series) && series.length ? series : [0,0,0,0,0,0];
      if (sparkCharts[id]) { sparkCharts[id].data.datasets[0].data = pts; sparkCharts[id].update('none'); return; }
      sparkCharts[id] = new window.Chart(c, {
        type: 'line',
        data: { labels: pts.map((_, i) => i + 1), datasets: [{ data: pts, borderColor: stroke, backgroundColor: fill, borderWidth: 1.6, fill: true, tension: 0.4, pointRadius: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false, beginAtZero: true } } }
      });
    }

    function renderSignals(data) {
      const m = (data && data.metrics) || {};
      const c = (data && data.chart_data) || {};

      const tv = document.getElementById('kpiTurnaroundValue');
      const tu = document.getElementById('kpiTurnaroundUnit');
      if (tv && tu) {
        if (m.turnaround_value != null && m.turnaround_value !== 'N/A') {
          tv.textContent = m.turnaround_value;
          tu.textContent = m.turnaround_unit || '';
        } else if (m.turnaround_days_raw != null) {
          tv.textContent = m.turnaround_days_raw;
          tu.textContent = m.turnaround_days_raw === 1 ? 'day' : 'days';
        } else {
          tv.textContent = 'N/A';
          tu.textContent = '';
        }
      }
      const cv = document.getElementById('kpiCompletionValue'); if (cv) cv.textContent = m.completion_rate ?? 0;
      const iv = document.getElementById('kpiInProgressValue'); if (iv) iv.textContent = m.in_progress_cases ?? 0;
      const pv = document.getElementById('kpiPendingValue'); if (pv) pv.textContent = m.pending_cases ?? 0;
      const av = document.getElementById('kpiApprovedValue'); if (av) av.textContent = m.approved_cases ?? 0;
      const ev = document.getElementById('kpiExhibitsValue'); if (ev) ev.textContent = m.total_exhibits ?? 0;
      const tot = document.getElementById('kpiTotalCasesValue'); if (tot) tot.textContent = m.total_cases ?? 0;

      const officialDefs = [
        { key: 'under_investigation', label: 'Under Investigation',         color: '#3b82f6' },
        { key: 'on_remand',           label: 'On Remand',                   color: '#6366f1' },
        { key: 'on_bail',             label: 'On Bail',                     color: '#0ea5e9' },
        { key: 'in_court',            label: 'In Court',                    color: '#f59e0b' },
        { key: 'verdict_imprisoned',  label: 'Verdict Passed - Imprisoned', color: '#dc2626' },
        { key: 'verdict_acquitted',   label: 'Verdict Passed - Acquitted',  color: '#10b981' },
        { key: 'case_dismissed',      label: 'Case Dismissed',              color: '#64748b' },
        { key: 'case_closed',         label: 'Case Closed',                 color: '#0f766e' }
      ];
      const officialCounts = {};
      officialDefs.forEach((d) => { officialCounts[d.key] = 0; });

      const caseRows = Array.isArray(data && data.cases) ? data.cases : [];
      caseRows.forEach((row) => {
        const s = String(row.status_badge || row.case_status || row.status || 'Under Investigation').toLowerCase();
        if (s.includes('remand')) officialCounts.on_remand++;
        else if (s.includes('bail')) officialCounts.on_bail++;
        else if (s.includes('court')) officialCounts.in_court++;
        else if (s.includes('imprisoned')) officialCounts.verdict_imprisoned++;
        else if (s.includes('acquitted')) officialCounts.verdict_acquitted++;
        else if (s.includes('dismissed')) officialCounts.case_dismissed++;
        else if (s.includes('closed')) officialCounts.case_closed++;
        else officialCounts.under_investigation++;
      });

      officialDefs.forEach((d) => {
        const el = document.getElementById('kpiSt_' + d.key);
        if (el) el.textContent = officialCounts[d.key];
      });

      const ranked = officialDefs
        .map((d) => ({ ...d, count: officialCounts[d.key] }))
        .sort((a, b) => b.count - a.count);

      const st1 = ranked[0];
      const st2 = ranked[1];
      const st3 = ranked[2];
      const totalCasesRaw = Number(m.total_cases ?? caseRows.length);
      const totalCasesNum = Math.max(1, totalCasesRaw);

      const topPctEl = document.getElementById('kpiTopStatusPct');
      const topLblEl = document.getElementById('kpiTopStatusLabel');
      const topCntEl = document.getElementById('kpiTopStatusCount');
      if (topPctEl) topPctEl.textContent = totalCasesRaw > 0 ? Math.round((st1.count / totalCasesRaw) * 100) : 0;
      if (topLblEl) topLblEl.textContent = totalCasesRaw > 0 ? st1.label : 'No cases';
      if (topCntEl) topCntEl.textContent = st1.count;

      const updateRing = (id, radius, item) => {
        const el = document.getElementById(id);
        if (!el) return;
        const circ = 2 * Math.PI * radius;
        const ratio = item.count > 0 ? Math.min(1, Math.max(0.06, item.count / totalCasesNum)) : 0;
        el.setAttribute('stroke', item.color);
        el.setAttribute('stroke-dasharray', circ.toFixed(2));
        el.setAttribute('stroke-dashoffset', (circ * (1 - ratio)).toFixed(2));
      };
      updateRing('ringStatus1', 76, st1);
      updateRing('ringStatus2', 64, st2);
      updateRing('ringStatus3', 52, st3);

      const hasApprovedSeries = Array.isArray(c.monthly_approved) && c.monthly_approved.some((v) => Number(v) > 0);
      sparkline('sparkTurnaround', hasApprovedSeries ? c.monthly_approved : c.monthly_bars, '#b45309', 'rgba(180,83,9,.12)');
      sparkline('sparkCompletion', c.monthly_approved, '#0b8f6f', 'rgba(11,143,111,.12)');
      sparkline('sparkInProgress', c.monthly_in_progress, '#1d4ed8', 'rgba(29,78,216,.12)');
      sparkline('sparkPending', c.monthly_pending, '#c2410c', 'rgba(194,65,12,.12)');
      sparkline('sparkExhibits', c.monthly_exhibits, '#0f766e', 'rgba(15,118,110,.12)');
      sparkline('sparkTotalCases', c.monthly_bars, '#475162', 'rgba(71,81,98,.10)');

      const barBadge = document.getElementById('docketsBarBadge');
      if (barBadge) {
        const totalD = Number(m.total_cases ?? 0);
        barBadge.textContent = totalD + (totalD === 1 ? ' docket' : ' dockets');
      }

      const barCanvas = document.getElementById('registeredDocketsBarChart');
      if (barCanvas && typeof window.Chart !== 'undefined') {
        const rawLabels = Array.isArray(c.months) && c.months.length ? c.months : ['M1', 'M2', 'M3', 'M4', 'M5', 'M6'];
        const labels = rawLabels.map(l => String(l).toUpperCase());
        const docketSeries = Array.isArray(c.monthly_bars) && c.monthly_bars.length ? c.monthly_bars : [0, 0, 0, 0, 0, 0];
        const exhibitSeries = Array.isArray(c.monthly_exhibits) && c.monthly_exhibits.length ? c.monthly_exhibits : [0, 0, 0, 0, 0, 0];

        const makeBarGrad = (chart, topColor, bottomColor) => {
          const { ctx, chartArea } = chart;
          if (!chartArea) return topColor;
          const grad = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
          grad.addColorStop(0, topColor);
          grad.addColorStop(1, bottomColor);
          return grad;
        };

        const modernBarPlugin = {
          id: 'modernBarEnhancements',
          beforeDatasetsDraw(chart) {
            const { ctx, chartArea } = chart;
            if (!chartArea) return;
            ctx.save();
            chart.data.datasets.forEach((ds, dsIdx) => {
              const meta = chart.getDatasetMeta(dsIdx);
              if (!meta || meta.hidden) return;
              meta.data.forEach((bar) => {
                const props = bar.getProps(['x', 'width'], true);
                if (!Number.isFinite(props.width) || props.width <= 0) return;
                const x = props.x - props.width / 2;
                const y = chartArea.top;
                const w = props.width;
                const h = chartArea.bottom - chartArea.top;
                const r = Math.min(7, w / 2);
                ctx.beginPath();
                if (typeof ctx.roundRect === 'function') {
                  ctx.roundRect(x, y, w, h, r);
                } else {
                  ctx.rect(x, y, w, h);
                }
                ctx.fillStyle = 'rgba(241, 245, 249, 0.85)';
                ctx.fill();
              });
            });
            ctx.restore();
          },
          afterDatasetsDraw(chart) {
            const { ctx } = chart;
            const labelColors = ['#4f46e5', '#0d9488'];
            ctx.save();
            ctx.font = '600 9.5px Inter, system-ui, sans-serif';
            ctx.textAlign = 'center';
            chart.data.datasets.forEach((ds, dsIdx) => {
              const meta = chart.getDatasetMeta(dsIdx);
              if (!meta || meta.hidden) return;
              ctx.fillStyle = labelColors[dsIdx % labelColors.length];
              meta.data.forEach((bar, idx) => {
                const val = Number(ds.data[idx] || 0);
                if (val <= 0) return;
                const props = bar.getProps(['x', 'y'], true);
                ctx.fillText(String(val), props.x, props.y - 5);
              });
            });
            ctx.restore();
          }
        };

        if (sparkCharts['registeredDocketsBarChart']) {
          const chart = sparkCharts['registeredDocketsBarChart'];
          chart.data.labels = labels;
          chart.data.datasets[0].data = docketSeries;
          chart.data.datasets[1].data = exhibitSeries;
          chart.update('none');
        } else {
          sparkCharts['registeredDocketsBarChart'] = new window.Chart(barCanvas, {
            type: 'bar',
            plugins: [modernBarPlugin],
            data: {
              labels: labels,
              datasets: [
                {
                  label: 'Dockets',
                  data: docketSeries,
                  backgroundColor: (ctx) => makeBarGrad(ctx.chart, '#38bdf8', '#4f46e5'),
                  hoverBackgroundColor: (ctx) => makeBarGrad(ctx.chart, '#0ea5e9', '#4338ca'),
                  borderRadius: 7,
                  borderSkipped: false,
                  barPercentage: 0.62,
                  categoryPercentage: 0.72
                },
                {
                  label: 'Exhibits',
                  data: exhibitSeries,
                  backgroundColor: (ctx) => makeBarGrad(ctx.chart, '#34d399', '#0d9488'),
                  hoverBackgroundColor: (ctx) => makeBarGrad(ctx.chart, '#10b981', '#0f766e'),
                  borderRadius: 7,
                  borderSkipped: false,
                  barPercentage: 0.62,
                  categoryPercentage: 0.72
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              animation: false,
              interaction: { mode: 'index', intersect: false },
              layout: { padding: { top: 18, right: 6, left: 2, bottom: 0 } },
              plugins: {
                legend: {
                  display: true,
                  position: 'top',
                  align: 'end',
                  labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    boxWidth: 7,
                    boxHeight: 7,
                    padding: 12,
                    color: '#475569',
                    font: { size: 10.5, weight: '600', family: 'Inter, system-ui, sans-serif' }
                  }
                },
                tooltip: {
                  backgroundColor: '#0f172a',
                  titleColor: '#f8fafc',
                  bodyColor: '#e2e8f0',
                  cornerRadius: 10,
                  padding: 10,
                  boxPadding: 5,
                  usePointStyle: true
                }
              },
              scales: {
                x: {
                  border: { display: false },
                  grid: { display: false },
                  ticks: {
                    color: '#64748b',
                    padding: 4,
                    font: { size: 10, weight: '600', family: 'Inter, system-ui, sans-serif' }
                  }
                },
                y: {
                  beginAtZero: true,
                  grace: '14%',
                  border: { display: false, dash: [4, 4] },
                  ticks: {
                    precision: 0,
                    color: '#94a3b8',
                    font: { size: 9.5, family: 'JetBrains Mono, monospace' }
                  },
                  grid: { color: 'rgba(226, 232, 240, 0.7)', tickLength: 0 }
                }
              }
            }
          });
        }
      }
    }

    function renderPreview(c) {
      const iconBox = document.getElementById('previewPriorityIcon');
      const status = document.getElementById('previewStatusLabel');
      const title = document.getElementById('previewSuspectTitle');
      const sub = document.getElementById('previewCaseSubline');
      const cnt = document.getElementById('previewExhibitCount');
      const cta = document.getElementById('previewReviewBtn');
      if (!iconBox || !status || !title || !sub || !cnt || !cta) return;

      if (!c) {
        iconBox.className = 'dock-priority';
        status.textContent = 'No matching dockets';
        title.textContent = 'No docket selected';
        sub.textContent = 'Clear filters or log a new case';
        cnt.textContent = '0';
        cta.href = @json($casesUrl);
        return;
      }

      const p = String(c.priority || 'HIGH').toUpperCase();
      let iconCls = 'dock-priority';
      if (p === 'MEDIUM') iconCls += ' med';
      else if (p === 'LOW') iconCls += ' low';
      iconBox.className = iconCls;

      status.textContent = (c.status_badge || 'Active').toUpperCase();
      title.textContent = c.suspect_name || c.title || 'Forensic docket';
      sub.textContent = (c.case_number || '') + ' · ' + (c.unit_name || 'Directorate');
      cnt.textContent = c.exhibits_count ?? 0;
      cta.href = REVIEW_BASE_URL + '/' + encodeURIComponent(c.case_number || c.id) + '/review';
    }

    function renderLollipopChart(el, buckets) {
      if (!el) return;
      const sum = buckets.reduce((a, b) => a + b.count, 0);
      const ticksHtml = [0, 25, 50, 75, 100].map((t) =>
        `<div class="stats-lollipop-tick" style="left:${t}%;"><span>${t}%</span></div>`
      ).join('');

      const rowsHtml = buckets.map((b) => {
        const pct = sum > 0 ? Math.round((b.count / sum) * 100) : 0;
        const stemPct = sum > 0 ? Math.max(2, Math.min(92, (b.count / sum) * 92)) : 0;
        return `<div class="stats-lollipop-row" title="${esc(b.label)}: ${b.count} (${pct}%)">`
          + `<span class="stats-lollipop-lbl">${esc(b.label)}</span>`
          + `<div class="stats-lollipop-lane">`
          + `<div class="stats-lollipop-stem" style="width:${stemPct.toFixed(1)}%;background:${b.color};"></div>`
          + `<span class="stats-lollipop-head" style="background:${b.color};">${b.count}</span>`
          + `</div>`
          + `<span class="stats-lollipop-pct">${pct}%</span>`
          + `</div>`;
      }).join('');

      el.innerHTML = `<div class="stats-lollipop-grid">${ticksHtml}</div>${rowsHtml}`;
    }

    function renderStats() {
      const list = filteredCases;
      const total = list.length;
      const scopeText = total + (total === 1 ? ' docket' : ' dockets');
      const sevScope = document.getElementById('severityScopeCount');
      const unitScope = document.getElementById('unitScopeCount');
      if (sevScope) sevScope.textContent = scopeText;
      if (unitScope) unitScope.textContent = scopeText;

      let hi = 0, lo = 0;
      const unitMap = {};
      list.forEach((c) => {
        const p = String(c.priority || 'HIGH').toUpperCase();
        if (p === 'CRITICAL' || p === 'HIGH') hi++;
        else lo++;

        const u = String(c.unit_name || '').toLowerCase();
        let k = null;
        if (u.includes('lab')) k = 'Lab';
        else if (u.includes('digital') || u.includes('cyber')) k = 'Digital';
        else if (u.includes('evidence')) k = 'Evidence';
        else if (u.includes('crime')) k = 'Crime scene';
        if (k) unitMap[k] = (unitMap[k] || 0) + 1;
      });

      const severityBuckets = [
        { label: 'High', count: hi, color: '#f43f5e' },
        { label: 'Low', count: lo, color: '#10b981' }
      ];

      const unitSorted = Object.entries(unitMap).sort((a, b) => b[1] - a[1]);
      const unitPal = ['#0ea5e9', '#6366f1', '#14b8a6', '#f59e0b'];
      const unitBuckets = (unitSorted.length
        ? unitSorted.slice(0, 4)
        : [['Lab', 0], ['Digital', 0], ['Evidence', 0], ['Crime scene', 0]]
      ).map(([label, count], i) => ({
        label,
        count,
        color: unitPal[i % unitPal.length]
      }));

      renderLollipopChart(document.getElementById('forensicSeverityLollipop'), severityBuckets);
      renderLollipopChart(document.getElementById('forensicUnitLollipop'), unitBuckets);
    }

    function renderTable() {
      const loading = document.getElementById('docketLoadingState');
      const err = document.getElementById('docketErrorState');
      const empty = document.getElementById('docketEmptyState');
      const wrap = document.getElementById('docketsTableWrap');
      const tbody = document.getElementById('docketsTableBody');
      const badge = document.getElementById('tableShowingBadge');
      const note = document.getElementById('tablePaginationNote');
      const toggle = document.getElementById('toggleShowAllBtn');
      const pill = document.getElementById('mappedCaseCountPill');

      if (loading) loading.style.display = 'none';
      if (err) err.style.display = 'none';
      if (pill) pill.textContent = filteredCases.length;

      if (filteredCases.length === 0) {
        if (wrap) wrap.style.display = 'none';
        if (empty) empty.style.display = 'block';
        if (badge) badge.textContent = '0 dockets';
        if (note) note.textContent = 'No matching dockets';
        if (toggle) toggle.style.display = 'none';
        renderPreview(null);
        return;
      }

      if (empty) empty.style.display = 'none';
      if (wrap) wrap.style.display = 'block';

      if (!selectedCaseId || !filteredCases.some((c) => String(c.case_number || c.id) === String(selectedCaseId))) {
        selectedCaseId = filteredCases[0].case_number || filteredCases[0].id;
      }
      const sel = filteredCases.find((c) => String(c.case_number || c.id) === String(selectedCaseId)) || filteredCases[0];
      renderPreview(sel);
      if (!tbody) return;

      const visible = showAllRows ? filteredCases.length : 6;
      const rows = filteredCases.slice(0, visible);

      if (badge) badge.textContent = filteredCases.length + (filteredCases.length === 1 ? ' docket' : ' dockets');
      if (note) note.textContent = 'Showing ' + rows.length + ' of ' + filteredCases.length;
      if (toggle) {
        if (filteredCases.length > 6) {
          toggle.style.display = 'inline-flex';
          toggle.textContent = showAllRows ? 'Show compact' : 'Show all (' + filteredCases.length + ')';
        } else {
          toggle.style.display = 'none';
        }
      }

      tbody.innerHTML = rows.map((c) => {
        const key = String(c.case_number || c.id);
        const isSel = key === String(selectedCaseId);
        const reviewHref = REVIEW_BASE_URL + '/' + encodeURIComponent(c.case_number || c.id) + '/review';
        const age = Number(c.days_open) === 0 ? 'Today' : Number(c.days_open) === 1 ? '1 day' : (c.days_open + ' days');
        const pCls = priorityClass(c.priority);
        const sCls = statusClass(c.status_badge);
        const pLabel = String(c.priority || 'HIGH').toUpperCase();

        return `
          <tr class="${isSel ? 'selected' : ''}" onclick="selectDashboardDocket('${esc(key)}')">
            <td>
              <div class="cell-suspect">
                <div class="id-badge">
                  <i data-lucide="file-text" style="width:13px;height:13px;"></i>
                </div>
                <div>
                  <div class="sname">${esc(c.suspect_name || c.title || 'N/A')}</div>
                  <div class="scase">${esc(c.case_number || '')}</div>
                </div>
              </div>
            </td>
            <td>${esc(c.unit_name || 'Directorate')}</td>
            <td><span class="chip-status ${sCls}">${esc(c.status_badge || 'Active')}</span></td>
            <td><span class="priority-mark ${pCls}">${esc(pLabel)}</span></td>
            <td class="mono" style="font-size:11px;color:var(--ink-500);">${esc(c.incident_date || 'N/A')}</td>
            <td style="font-weight:500;">${esc(age)}</td>
            <td style="text-align:right;">
              <a href="${reviewHref}" onclick="event.stopPropagation()" class="btn-row">Inspect</a>
            </td>
          </tr>
        `;
      }).join('');

      icons();
    }

    window.applyDashboardFilters = function () {
      if (!telemetryPayload) return;
      const all = Array.isArray(telemetryPayload.cases) ? telemetryPayload.cases : [];
      const period = (document.getElementById('filterPeriod')?.value || 'all').toLowerCase();
      const region = document.getElementById('filterRegion')?.value || 'all';
      const unit = (document.getElementById('filterUnit')?.value || 'all').toLowerCase();
      const status = (document.getElementById('filterStatus')?.value || 'all').toLowerCase();
      const priority = (document.getElementById('filterPriority')?.value || 'all').toUpperCase();
      const q = (document.getElementById('globalSearch')?.value || '').trim().toLowerCase();

      filteredCases = all.filter((c) => {
        const d = Number(c.days_open ?? 0);
        if (period === 'today' && d > 0) return false;
        if (period === '7d' && d > 7) return false;
        if (period === '30d' && d > 30) return false;
        if (region !== 'all' && String(c.region || '').toLowerCase() !== region.toLowerCase()) return false;
        if (unit !== 'all') {
          const cu = String(c.unit_name || '').toLowerCase();
          if (!cu.includes(unit.split(' ')[0])) return false;
        }
        if (status !== 'all' && normStatus(c.status_badge) !== status) return false;
        if (priority !== 'ALL' && String(c.priority || '').toUpperCase() !== priority) return false;
        if (q) {
          const hay = [c.case_number, c.suspect_name, c.title, c.raid_location, c.region, c.unit_name, c.status_badge, c.submitting_officer].join(' ').toLowerCase();
          if (!hay.includes(q)) return false;
        }
        return true;
      });

      // Mirror the bottom toolbar filters to match the map filters
      const sb = document.getElementById('filterStatusBar'); if (sb) sb.value = status;
      const pb = document.getElementById('filterPriorityBar'); if (pb) pb.value = priority.toLowerCase() === 'all' ? 'all' : priority;

      renderTable();
      renderStats();

      const mappedCases = all.filter((c) => (
        c.latitude !== null && c.latitude !== undefined && c.latitude !== '' &&
        c.longitude !== null && c.longitude !== undefined && c.longitude !== ''
      ));
      const pill = document.getElementById('mappedCaseCountPill');
      if (pill) pill.textContent = mappedCases.length;

      const ctrl = window.ForensicLeafletMaps && window.ForensicLeafletMaps['forensicMapContainer'];
      if (ctrl && typeof ctrl.setMarkers === 'function') {
        ctrl.setMarkers(mappedCases.map((c) => ({
          id: c.id, case_number: c.case_number, title: c.suspect_name || c.title,
          unit_name: c.unit_name, status: c.status_badge, priority: c.priority,
          location: c.raid_location || c.region, region: c.region,
          exhibits_count: c.exhibits_count, latitude: c.latitude, longitude: c.longitude,
          review_url: REVIEW_BASE_URL + '/' + encodeURIComponent(c.case_number || c.id) + '/review'
        })));
      }
    };

    // Keep map filters and toolbar filters in sync
    window.syncBarToFilter = function (fromId, toId, value) {
      const to = document.getElementById(toId);
      if (!to) return;
      // map filter expects lowercase 'all' or upper priority tokens
      if (toId === 'filterPriority') {
        to.value = value === 'all' ? 'all' : value.toUpperCase();
      } else {
        to.value = value;
      }
      window.applyDashboardFilters();
    };

    window.quickFilterByStatus = function (s) {
      const el = document.getElementById('filterStatus');
      if (el) { el.value = s; window.applyDashboardFilters(); }
    };

    window.resetDashboardFilters = function () {
      ['filterPeriod','filterRegion','filterStatus','filterPriority'].forEach((id) => {
        const el = document.getElementById(id); if (el) el.value = 'all';
      });
      const u = document.getElementById('filterUnit'); if (u) u.value = DEFAULT_UNIT_FILTER || 'all';
      const s = document.getElementById('globalSearch'); if (s) s.value = '';
      const sb = document.getElementById('filterStatusBar'); if (sb) sb.value = 'all';
      const pb = document.getElementById('filterPriorityBar'); if (pb) pb.value = 'all';
      showAllRows = false;
      window.applyDashboardFilters();
    };

    window.selectDashboardDocket = function (key) {
      selectedCaseId = key;
      renderTable();
      const chosen = filteredCases.find((c) => String(c.case_number || c.id) === String(key));
      const ctrl = window.ForensicLeafletMaps && window.ForensicLeafletMaps['forensicMapContainer'];
      if (chosen && ctrl && typeof ctrl.focusCase === 'function') ctrl.focusCase(chosen);
    };

    window.toggleShowAllRows = function () { showAllRows = !showAllRows; renderTable(); };

    window.toggleMobileNav = function () {
      const panel = document.getElementById('mobileNavPanel');
      if (!panel) return;
      const isHidden = panel.style.display === 'none' || !panel.style.display;
      panel.style.display = isHidden ? 'block' : 'none';
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
      }
    };

    window.refreshRealtimeData = async function (isBackground) {
      const sp = document.getElementById('syncSpinner');
      const loading = document.getElementById('docketLoadingState');
      const err = document.getElementById('docketErrorState');
      if (sp && !isBackground) sp.classList.add('spin');
      if (!telemetryPayload && loading) loading.style.display = 'block';
      if (err) err.style.display = 'none';

      try {
        const res = await fetch(REALTIME_ENDPOINT, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (!data || data.success === false) throw new Error('Invalid payload');

        const nextHash = JSON.stringify({
          m: data.metrics || {},
          c: Array.isArray(data.cases) ? data.cases.map((x) => [
            x.id, x.case_number, x.status_badge, x.priority, x.exhibits_count, x.latitude, x.longitude, x.updated_at_formatted
          ]) : []
        });

        lastTelemetryHash = nextHash;
        telemetryPayload = data;
        renderSignals(data);
        window.applyDashboardFilters();
      } catch (e) {
        if (loading) loading.style.display = 'none';
        if (err) err.style.display = 'block';
        const m = document.getElementById('docketErrorMessage');
        if (m) m.textContent = 'Unable to load forensic telemetry (' + e.message + ').';
      } finally {
        if (sp) sp.classList.remove('spin');
      }
    };

    document.addEventListener('DOMContentLoaded', () => {
      icons();
      const u = document.getElementById('filterUnit');
      if (u && DEFAULT_UNIT_FILTER) u.value = DEFAULT_UNIT_FILTER;
      window.refreshRealtimeData(false);
      setInterval(() => window.refreshRealtimeData(true), 30000);
    });
  })();
</script>
</body>
</html>