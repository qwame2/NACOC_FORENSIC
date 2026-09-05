<!-- LEFT EXECUTIVE NAVIGATION SIDEBAR -->
<aside style="width:248px; background:linear-gradient(180deg,#060d1f 0%,#080f1e 100%); border-right:1px solid rgba(59,130,246,0.1); display:flex; flex-direction:column; flex-shrink:0; user-select:none; font-family:'Inter',system-ui,sans-serif;">

    <!-- BRAND / LOGO BAR -->
    <div style="height:64px; padding:0 20px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(59,130,246,0.1); background:rgba(0,0,0,0.3);">
        <a href="#" style="display:flex; align-items:center; gap:10px; text-decoration:none; outline:none;">
            <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#1d4ed8,#3b82f6); display:flex; align-items:center; justify-content:center; box-shadow:0 0 18px rgba(59,130,246,0.45), 0 0 40px rgba(59,130,246,0.15); flex-shrink:0;">
                <svg style="width:20px;height:20px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <div style="font-size:11px; font-weight:800; color:white; letter-spacing:0.08em; font-family:'JetBrains Mono',monospace; line-height:1.2;">NACOC FORENSIC</div>
                <div style="font-size:9px; font-weight:700; color:#60a5fa; letter-spacing:0.12em; text-transform:uppercase; margin-top:2px;">DEPT HEAD PORTAL</div>
            </div>
        </a>
        <span style="font-size:9px; font-weight:800; font-family:'JetBrains Mono',monospace; color:#60a5fa; background:rgba(59,130,246,0.12); border:1px solid rgba(59,130,246,0.25); border-radius:5px; padding:2px 7px; letter-spacing:0.08em;">EXEC</span>
    </div>

    <!-- SCROLLABLE NAV -->
    <div style="flex:1; overflow-y:auto; padding:16px 12px; display:flex; flex-direction:column; gap:20px;">

        <!-- DIRECTORATE COMMAND -->
        <div>
            <div style="font-size:9px; font-weight:800; font-family:'JetBrains Mono',monospace; color:rgba(99,102,241,0.7); letter-spacing:0.14em; text-transform:uppercase; padding:0 12px; margin-bottom:8px;">Directorate Command</div>
            <nav style="display:flex; flex-direction:column; gap:2px;">

                <a href="{{ url('/Forensic_Head') }}" style="display:flex; align-items:center; padding:9px 12px; border-radius:10px; background:linear-gradient(135deg,rgba(37,99,235,0.28),rgba(59,130,246,0.14)); border:1px solid rgba(59,130,246,0.28); text-decoration:none; box-shadow:0 0 14px rgba(59,130,246,0.1);">
                    <svg style="width:18px;height:18px;color:#60a5fa;margin-right:10px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span style="font-size:12px; font-weight:700; color:#93c5fd;">Executive Overview</span>
                </a>

                <a href="{{ url('/cases') }}" style="display:flex; align-items:center; padding:9px 12px; border-radius:10px; text-decoration:none; color:rgba(148,163,184,0.8); transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="this.style.background='';this.style.color='rgba(148,163,184,0.8)';">
                    <svg style="width:18px;height:18px;color:#60a5fa;margin-right:10px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span style="font-size:12px; font-weight:500; flex:1;">Master Case Directory</span>
                    <span style="font-size:10px; font-weight:700; font-family:'JetBrains Mono',monospace; background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.25); border-radius:12px; padding:2px 7px;">148</span>
                </a>

                <a href="#pendingApprovals" style="display:flex; align-items:center; padding:9px 12px; border-radius:10px; text-decoration:none; color:rgba(148,163,184,0.8); transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="this.style.background='';this.style.color='rgba(148,163,184,0.8)';">
                    <svg style="width:18px;height:18px;color:rgba(148,163,184,0.5);margin-right:10px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span style="font-size:12px; font-weight:500; flex:1;">Head Approvals &amp; Sign-off</span>
                    <span style="font-size:10px; font-weight:700; font-family:'JetBrains Mono',monospace; background:rgba(245,158,11,0.12); color:#fbbf24; border:1px solid rgba(245,158,11,0.25); border-radius:12px; padding:2px 7px;">12</span>
                </a>

                <a href="#unitPerformance" style="display:flex; align-items:center; padding:9px 12px; border-radius:10px; text-decoration:none; color:rgba(148,163,184,0.8); transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="this.style.background='';this.style.color='rgba(148,163,184,0.8)';">
                    <svg style="width:18px;height:18px;color:rgba(148,163,184,0.5);margin-right:10px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span style="font-size:12px; font-weight:500; flex:1;">4 Units Oversight</span>
                    <span style="font-size:10px; font-weight:700; font-family:'JetBrains Mono',monospace; background:rgba(100,116,139,0.15); color:#94a3b8; border:1px solid rgba(100,116,139,0.2); border-radius:12px; padding:2px 7px;">4 Units</span>
                </a>

                <a href="#" style="display:flex; align-items:center; padding:9px 12px; border-radius:10px; text-decoration:none; color:rgba(148,163,184,0.8); transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="this.style.background='';this.style.color='rgba(148,163,184,0.8)';">
                    <svg style="width:18px;height:18px;color:rgba(148,163,184,0.5);margin-right:10px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span style="font-size:12px; font-weight:500; flex:1;">Court Evidence Reports</span>
                </a>

            </nav>
        </div>

        <!-- SIDEBAR UNIT QUICK-FILTER -->
        <div>
            <div style="font-size:9px; font-weight:800; font-family:'JetBrains Mono',monospace; color:rgba(99,102,241,0.7); letter-spacing:0.14em; text-transform:uppercase; padding:0 12px; margin-bottom:8px;">Quick Filter by Unit</div>
            <nav style="display:flex; flex-direction:column; gap:2px;">

                <button onclick="filterDeptHeadUnit('all', this)" class="sidebar-unit-btn" style="display:flex; align-items:center; padding:8px 12px; border-radius:10px; background:transparent; border:none; cursor:pointer; color:rgba(148,163,184,0.7); width:100%; text-align:left; transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="if(!this.classList.contains('sb-active')){this.style.background='';this.style.color='rgba(148,163,184,0.7)';}">
                    <span style="width:10px;height:10px;border-radius:50%;background:rgba(148,163,184,0.4);margin-right:10px;flex-shrink:0;display:inline-block;"></span>
                    <span style="font-size:11px; font-weight:600;">All Units</span>
                </button>

                <button onclick="filterDeptHeadUnit('Crime Scene Support', this)" class="sidebar-unit-btn" style="display:flex; align-items:center; padding:8px 12px; border-radius:10px; background:transparent; border:none; cursor:pointer; color:rgba(148,163,184,0.7); width:100%; text-align:left; transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="if(!this.classList.contains('sb-active')){this.style.background='';this.style.color='rgba(148,163,184,0.7)';}">
                    <span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;margin-right:10px;flex-shrink:0;display:inline-block;box-shadow:0 0 6px rgba(59,130,246,0.5);"></span>
                    <span style="font-size:11px; font-weight:600;">Crime Scene Support</span>
                </button>

                <button onclick="filterDeptHeadUnit('Evidence Management', this)" class="sidebar-unit-btn" style="display:flex; align-items:center; padding:8px 12px; border-radius:10px; background:transparent; border:none; cursor:pointer; color:rgba(148,163,184,0.7); width:100%; text-align:left; transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="if(!this.classList.contains('sb-active')){this.style.background='';this.style.color='rgba(148,163,184,0.7)';}">
                    <span style="width:10px;height:10px;border-radius:50%;background:#6366f1;margin-right:10px;flex-shrink:0;display:inline-block;box-shadow:0 0 6px rgba(99,102,241,0.5);"></span>
                    <span style="font-size:11px; font-weight:600;">Evidence Management</span>
                </button>

                <button onclick="filterDeptHeadUnit('LAB Analysis', this)" class="sidebar-unit-btn" style="display:flex; align-items:center; padding:8px 12px; border-radius:10px; background:transparent; border:none; cursor:pointer; color:rgba(148,163,184,0.7); width:100%; text-align:left; transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="if(!this.classList.contains('sb-active')){this.style.background='';this.style.color='rgba(148,163,184,0.7)';}">
                    <span style="width:10px;height:10px;border-radius:50%;background:#10b981;margin-right:10px;flex-shrink:0;display:inline-block;box-shadow:0 0 6px rgba(16,185,129,0.5);"></span>
                    <span style="font-size:11px; font-weight:600;">LAB Analysis</span>
                </button>

                <button onclick="filterDeptHeadUnit('Digital', this)" class="sidebar-unit-btn" style="display:flex; align-items:center; padding:8px 12px; border-radius:10px; background:transparent; border:none; cursor:pointer; color:rgba(148,163,184,0.7); width:100%; text-align:left; transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="if(!this.classList.contains('sb-active')){this.style.background='';this.style.color='rgba(148,163,184,0.7)';}">
                    <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;margin-right:10px;flex-shrink:0;display:inline-block;box-shadow:0 0 6px rgba(245,158,11,0.5);"></span>
                    <span style="font-size:11px; font-weight:600;">Digital &amp; Physical</span>
                </button>

            </nav>
        </div>

        <!-- GOVERNANCE -->
        <div>
            <div style="font-size:9px; font-weight:800; font-family:'JetBrains Mono',monospace; color:rgba(100,116,139,0.6); letter-spacing:0.14em; text-transform:uppercase; padding:0 12px; margin-bottom:8px;">Directorate Governance</div>
            <nav style="display:flex; flex-direction:column; gap:2px;">

                <a href="#" style="display:flex; align-items:center; padding:9px 12px; border-radius:10px; text-decoration:none; color:rgba(148,163,184,0.8); transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="this.style.background='';this.style.color='rgba(148,163,184,0.8)';">
                    <svg style="width:18px;height:18px;color:rgba(148,163,184,0.45);margin-right:10px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span style="font-size:12px; font-weight:500;">Personnel Roster (54 Staff)</span>
                </a>

                <a href="#" style="display:flex; align-items:center; padding:9px 12px; border-radius:10px; text-decoration:none; color:rgba(148,163,184,0.8); transition:all 0.2s;" onmouseover="this.style.background='rgba(59,130,246,0.08)';this.style.color='#e2e8f0';" onmouseout="this.style.background='';this.style.color='rgba(148,163,184,0.8)';">
                    <svg style="width:18px;height:18px;color:rgba(148,163,184,0.45);margin-right:10px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span style="font-size:12px; font-weight:500;">ISO 17025 Audit Logs</span>
                </a>

            </nav>
        </div>

    </div>

    <!-- DEPT HEAD PROFILE BANNER -->
    @php
        $authUser = auth()->user();
        $userName = $authUser->fullname ?? session('user_name') ?? 'Adomako Emmanuel';
        $nameParts = preg_split('/\s+/', trim($userName));
        $initials = count($nameParts) >= 2 
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($userName, 0, 2));
        $userRole = ($authUser && $authUser->is_head) ? 'Forensic Head · NACOC' : 'Commanding Officer · NACOC';
    @endphp
    <div style="padding:14px; border-top:1px solid rgba(59,130,246,0.1); background:rgba(0,0,0,0.25);">
        <div style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06);">
            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:white;box-shadow:0 0 12px rgba(59,130,246,0.35);flex-shrink:0;">
                {{ $initials }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:11px; font-weight:700; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $userName }}</div>
                <div style="font-size:9px; color:#60a5fa; font-family:'JetBrains Mono',monospace; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; letter-spacing:0.04em;">{{ $userRole }}</div>
            </div>
            <div style="width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 8px rgba(16,185,129,0.6);flex-shrink:0;"></div>
        </div>
    </div>

</aside>