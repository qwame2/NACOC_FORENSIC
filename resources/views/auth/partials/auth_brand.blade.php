@php($authMode = $mode ?? 'login')
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
        @if ($authMode === 'register')
            <h2 class="auth-brand__headline">Enrol once. Sign every <em>handover</em>.</h2>
            <p class="auth-brand__lede">Your service credentials tie each action in the exhibit record to a named
                officer. Your Head of Unit approves the account before first sign in.</p>
        @elseif ($authMode === 'forgot')
            <h2 class="auth-brand__headline">Restore your <em>access</em> safely.</h2>
            <p class="auth-brand__lede">Submit a password reset request to your Head of Unit. As soon as your Head approves it, a 6-digit OTP code is sent straight to the Gmail account registered on FIMS.</p>
        @elseif ($authMode === 'reset')
            <h2 class="auth-brand__headline">Set a new <em>credential</em> key.</h2>
            <p class="auth-brand__lede">Choose a strong passphrase unique to your forensic workstation. Your OTP
                session is invalidated the moment you save.</p>
        @else
            <h2 class="auth-brand__headline">Every exhibit, <em>accounted</em> for.</h2>
            <p class="auth-brand__lede">Sign in to the Forensic Information Management System to lodge, examine and
                report on evidence with a complete chain of custody.</p>
        @endif
    </div>

    @if ($authMode === 'forgot' || $authMode === 'reset')
        <ol class="custody" aria-label="Credential recovery steps in FIMS">
            <li><strong>Request Unit Head approval</strong>Submit your registered Gmail, username, or Service ID</li>
            <li><strong>Enter 6-digit Gmail OTP</strong>Once approved by your Head, enter the 5-minute code sent to Gmail</li>
            <li><strong>Set new passphrase</strong>Save compliant credentials and return straight to sign in</li>
        </ol>
    @else
        <ol class="custody" aria-label="Exhibit lifecycle in FIMS">
            <li><strong>Seized and lodged</strong>Crime scene and evidence management record the exhibit</li>
            <li><strong>Examined</strong>Laboratory and digital forensics add their findings</li>
            <li><strong>Reviewed</strong>The Head of Forensics reviews submissions</li>
        </ol>
    @endif

    <div class="auth-brand__foot font-mono-code">
        <span>FIMS portal</span>
        <span id="liveClock">--</span>
    </div>
</aside>
