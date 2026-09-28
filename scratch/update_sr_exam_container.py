import re

filepath = r"c:\xampp\htdocs\ForensicNacoc\resources\views\cases\show_review.blade.php"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Replace section5FormContainer header block with new distinct container layout
old_start = '''                <!-- EXAMINATION INFORMATION CONTAINER (ALWAYS VISIBLE AT LAB ANALYSIS STAGE WHEN UNSAVED) -->
                <div id="section5FormContainer" class="space-y-6 {{ $activeSuspectHasSavedResult ? 'hidden' : '' }}">
                    <div class="pb-3 border-b border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">

                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-slate-900 text-base">Examination Information</h3>
                                    <span id="section5BadgeStatus" class="px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase {{ $activeSuspectHasSavedResult ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($labStatus === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-800 border border-slate-300') }}">
                                        {{ $activeSuspectHasSavedResult ? '✓ LAB COMPLETED' : ($labStatus === 'IN_PROGRESS' ? 'LAB IN PROGRESS' : 'LAB PENDING') }}
                                    </span>
                                </div>
                                <p class="text-xs text-emerald-700 font-semibold">Laboratory Analysis Directorate — Forensic Examination &amp; Testing Log</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                LAB ANALYSIS EXCLUSIVE
                            </span>
                            <button type="button" onclick="toggleSection5()" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-extrabold rounded-lg border border-slate-300 transition-colors flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.025 10.025 0 012.122-.363c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                                <span class="s5ToggleTextSpan">Hide Section 5</span>
                            </button>
                        </div>
                    </div>'''

new_start = '''                <!-- EXAMINATION INFORMATION CONTAINER (DISTINCT LAB ANALYSIS CONTAINER CARD) -->
                <div id="section5FormContainer" class="space-y-6 bg-gradient-to-br from-emerald-50/40 via-white to-slate-50/70 p-6 sm:p-7 rounded-3xl border-2 border-emerald-400/80 shadow-xl shadow-emerald-950/10 backdrop-blur-xs transition-all {{ $activeSuspectHasSavedResult ? 'hidden' : '' }}">
                    <!-- DISTINCT TOP HEADER BANNER -->
                    <div class="p-4 sm:p-5 bg-gradient-to-r from-emerald-900 via-slate-900 to-emerald-950 text-white rounded-2xl shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-emerald-700/60">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 flex items-center justify-center font-bold shadow-xs shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-black text-white text-base tracking-tight">Examination Information</h3>
                                    <span id="section5BadgeStatus" class="px-2.5 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase {{ $activeSuspectHasSavedResult ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/40' : ($labStatus === 'IN_PROGRESS' ? 'bg-amber-500/20 text-amber-300 border border-amber-400/40' : 'bg-slate-700/60 text-slate-300 border border-slate-600') }}">
                                        {{ $activeSuspectHasSavedResult ? '✓ LAB COMPLETED' : ($labStatus === 'IN_PROGRESS' ? 'LAB IN PROGRESS' : 'LAB PENDING') }}
                                    </span>
                                </div>
                                <p class="text-xs text-emerald-300/90 font-medium mt-0.5">Laboratory Analysis Directorate — Forensic Examination &amp; Testing Log</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <span class="px-2.5 py-1 bg-emerald-800/60 text-emerald-200 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-600/50 uppercase tracking-wider">
                                LAB ANALYSIS EXCLUSIVE
                            </span>
                            <button type="button" onclick="toggleSection5()" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-[11px] font-extrabold rounded-lg border border-white/20 transition-colors flex items-center gap-1.5 cursor-pointer backdrop-blur-xs">
                                <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.025 10.025 0 012.122-.363c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                                <span class="s5ToggleTextSpan">Hide Section 5</span>
                            </button>
                        </div>
                    </div>'''

if old_start in content:
    content = content.replace(old_start, new_start)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Successfully updated section5FormContainer in show_review.blade.php")
else:
    print("Could not find exact old_start match")
