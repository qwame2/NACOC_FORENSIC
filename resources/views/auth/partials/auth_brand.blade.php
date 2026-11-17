@php($isRegister = ($mode ?? 'login') === 'register')
<aside class="auth-brand">
    <div class="flex items-center gap-5">
        <span class="auth-brand__crest-wrap">
            <img src="{{ asset('nacoc.jfif') }}" alt="NACOC emblem" width="104" height="104"
                class="auth-brand__crest object-contain">
        </span>
        <div>
            <div class="text-base font-extrabold text-white leading-tight">Narcotics Control Commission</div>
            <div class="text-sm text-[#8fb3ae]">Forensics Department, Ghana</div>
        </div>
    </div>

    <div class="space-y-4">
        @if ($isRegister)
            <h2 class="auth-brand__headline">Enrol once. Sign every <em>handover</em>.</h2>
            <p class="auth-brand__lede">Your service credentials tie each action in the exhibit record to a named
                officer. Your Head of Unit approves the account before first sign in.</p>
        @else
            <h2 class="auth-brand__headline">Every exhibit, <em>accounted</em> for.</h2>
            <p class="auth-brand__lede">Sign in to the Forensic Information Management System to lodge, examine and
                report on evidence with a complete chain of custody.</p>
        @endif
    </div>

    <ol class="custody" aria-label="Exhibit lifecycle in FIMS">
        <li><strong>Seized and lodged</strong>Crime scene and evidence management record the exhibit</li>
        <li><strong>Examined</strong>Laboratory and digital forensics add their findings</li>
        <li><strong>Reviewed</strong>The Head of Forensics reviews submissions</li>
    </ol>

    <div class="auth-brand__foot font-mono-code">
        <span>FIMS portal</span>
        <span id="liveClock">--</span>
    </div>
</aside>

