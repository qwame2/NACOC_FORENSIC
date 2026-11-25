<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap"
    rel="stylesheet">

<style>
    :root {
        --ink: #052b29;
        --ink-2: #0a3d3a;
        --teal: #0f766e;
        --teal-deep: #115e59;
        --paper: #f5f7f6;
        --line: #d9e1df;
        --brass: #d4a24c;
    }

    body {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        background: var(--paper);
        color: #0f172a;
        margin: 0;
        padding: 0;
    }

    .font-mono-code {
        font-family: 'JetBrains Mono', monospace;
    }

    .font-display {
        font-family: 'Fraunces', Georgia, serif;
        font-optical-sizing: auto;
    }

    /* ---------- Split-screen shell ---------- */
    .auth-shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
    }

    @media (min-width: 1024px) {
        .auth-shell {
            grid-template-columns: minmax(380px, 5fr) minmax(0, 7fr);
        }

        .auth-shell.auth-shell--wide {
            grid-template-columns: minmax(340px, 4fr) minmax(0, 8fr);
        }
    }

    /* ---------- Brand panel ---------- */
    .auth-brand {
        position: relative;
        background: var(--ink);
        color: #e6f2f0;
        padding: 28px 28px 24px;
        display: flex;
        flex-direction: column;
        gap: 28px;
        overflow: hidden;
    }

    @media (min-width: 1024px) {
        .auth-brand {
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 44px 48px 36px;
            justify-content: space-between;
        }
    }

    .auth-brand::after {
        /* one diagonal facet carried over from the previous mint design */
        content: '';
        position: absolute;
        right: -30%;
        bottom: -20%;
        width: 90%;
        height: 70%;
        background: var(--ink-2);
        clip-path: polygon(30% 0, 100% 0, 100% 100%, 0 100%);
        pointer-events: none;
    }

    .auth-brand>* {
        position: relative;
        z-index: 1;
    }

    .auth-brand__crest-wrap {
        position: relative;
        display: inline-flex;
        flex-shrink: 0;
    }

    .auth-brand__crest {
        width: 84px;
        height: 84px;
        border-radius: 22px;
        background: #fff;
        padding: 8px;
    }

    @media (min-width: 1024px) {
        .auth-brand__crest {
            width: 104px;
            height: 104px;
            border-radius: 26px;
            padding: 10px;
        }
    }

    /* Form-panel header & crest */
    .auth-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .auth-logo {
        width: 140px;
        height: 140px;
        object-fit: contain;
        margin: 0 auto 16px;
        filter: drop-shadow(0 4px 10px rgba(5, 43, 41, 0.08));
    }

    @media (min-width: 640px) {
        .auth-logo {
            width: 160px;
            height: 160px;
        }
    }

    .auth-logo--login {
        width: 350px;
        height: 350px;
        max-width: 100%;
        margin: -6px auto 10px;
    }

    @media (min-width: 640px) {
        .auth-logo--login {
            width: 350px;
            height: 350px;
            margin: -10px auto 12px;
        }
    }

    /* Prominent switch between sign in and register */
    .auth-switch {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 6px 6px 6px 16px;
        border: 1px solid rgba(217, 225, 223, 0.85);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(15, 118, 110, 0.04);
        transition: all .2s ease;
    }

    .auth-switch:hover {
        border-color: rgba(15, 118, 110, 0.3);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06), 0 6px 16px rgba(15, 118, 110, 0.08);
    }

    .auth-switch a {
        background: linear-gradient(135deg, #052b29 0%, #0d4a45 100%);
        color: #fff;
        font-weight: 700;
        font-size: 12.5px;
        padding: 7px 16px;
        border-radius: 999px;
        box-shadow: 0 2px 6px rgba(5, 43, 41, 0.25);
        transition: all .15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .auth-switch a:hover {
        background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
        box-shadow: 0 4px 10px rgba(15, 118, 110, 0.35);
        text-decoration: none;
    }

    .auth-switch a:focus-visible {
        outline: 3px solid var(--brass);
        outline-offset: 2px;
    }


    .auth-brand__headline {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 700;
        font-size: clamp(1.9rem, 3.2vw, 2.9rem);
        line-height: 1.08;
        letter-spacing: -0.02em;
        color: #fff;
        max-width: 14ch;
    }

    .auth-brand__headline em {
        font-style: normal;
        color: var(--brass);
    }

    .auth-brand__lede {
        font-size: 0.9rem;
        line-height: 1.6;
        color: #a9c8c4;
        max-width: 38ch;
    }

    /* custody chain: the real lifecycle of an exhibit in FIMS */
    .custody {
        list-style: none;
        margin: 0;
        padding: 0;
        display: none;
    }

    @media (min-width: 1024px) {
        .custody {
            display: block;
        }
    }

    .custody li {
        position: relative;
        padding: 0 0 18px 30px;
        font-size: 0.82rem;
        color: #cfe3e0;
    }

    .custody li:last-child {
        padding-bottom: 0;
    }

    .custody li::before {
        content: '';
        position: absolute;
        left: 5px;
        top: 14px;
        bottom: -2px;
        width: 1px;
        background: #2b5753;
    }

    .custody li:last-child::before {
        display: none;
    }

    .custody li::after {
        content: '';
        position: absolute;
        left: 0;
        top: 4px;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        border: 2px solid #4e8a84;
        background: var(--ink);
    }

    .custody li strong {
        display: block;
        color: #fff;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .custody li:first-child::after {
        border-color: var(--brass);
        background: var(--brass);
    }

    .auth-brand__foot {
        font-size: 11px;
        color: #7fa6a1;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* ---------- Form panel ---------- */
    .auth-panel {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        padding: 28px 22px 24px;
        background: radial-gradient(circle at 85% 10%, rgba(15, 118, 110, 0.035) 0%, transparent 45%),
                    radial-gradient(circle at 15% 90%, rgba(212, 162, 76, 0.03) 0%, transparent 40%),
                    var(--paper);
    }

    @media (min-width: 640px) {
        .auth-panel {
            padding: 36px 56px 28px;
        }
    }

    .auth-panel__inner {
        width: 100%;
        max-width: 480px;
        margin: 0 auto auto;
        padding: 12px 0 28px;
    }

    .auth-panel--login {
        padding-top: 18px;
    }

    .auth-panel__inner--login {
        margin-top: 0;
        padding-top: 0;
    }

    @media (min-width: 640px) {
        .auth-panel--login {
            padding-top: 20px;
        }
    }

    @media (min-width: 1024px) {
        .auth-panel__inner {
            margin: 2vh auto auto;
            padding: 8px 0 24px;
        }

        .auth-panel__inner--login {
            margin-top: 0;
            padding-top: 0;
        }
    }

    .auth-panel__inner--wide {
        max-width: 760px;
    }


    .auth-title {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 700;
        font-size: 2.35rem;
        letter-spacing: -0.025em;
        line-height: 1.1;
        color: var(--ink);
    }

    .auth-sub {
        margin-top: 8px;
        font-size: 0.92rem;
        line-height: 1.5;
        color: #55706c;
    }

    /* Executive Glass Card Container for modern feel */
    .auth-card {
        background: #ffffff;
        border: 1px solid rgba(217, 225, 223, 0.9);
        border-radius: 24px;
        padding: 28px 30px;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 
                    0 12px 32px -4px rgba(5, 43, 41, 0.06);
        position: relative;
    }

    @media (min-width: 640px) {
        .auth-card {
            padding: 32px 34px;
        }
    }

    /* Boxed field: modern floating feel with crisp active glow */
    .ref-input-line {
        background: #f8faf9;
        border: 1.5px solid var(--line);
        border-radius: 14px;
        padding: 10px 14px 9px !important;
        transition: all .2s ease;
    }

    .ref-input-line:hover {
        background: #ffffff;
        border-color: #94a3b8;
    }

    .ref-input-line:focus-within {
        background: #ffffff;
        border-color: var(--teal);
        box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
    }

    .ref-input-line label {
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        font-family: inherit !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #475569 !important;
        margin-bottom: 2px !important;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ref-input-line input:not([type=checkbox]):not([type=radio]),
    .ref-input-line select,
    .ref-input-line textarea {
        font-size: 14px;
        color: #0f172a;
    }

    /* Primary action: gradient, micro-shadow and tactile active state */
    .auth-cta {
        width: 100%;
        padding: 14px 22px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0f766e 0%, #0d5f58 100%);
        color: #fff;
        font-weight: 800;
        font-size: 14.5px;
        letter-spacing: .015em;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        border: 0;
        box-shadow: 0 4px 14px rgba(15, 118, 110, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        transition: all .18s ease;
    }

    .auth-cta:hover {
        background: linear-gradient(135deg, #115e59 0%, #0a4640 100%);
        box-shadow: 0 6px 18px rgba(15, 118, 110, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.25);
    }

    .auth-cta:active {
        transform: translateY(1px);
        box-shadow: 0 2px 8px rgba(15, 118, 110, 0.3);
    }

    .auth-cta:disabled {
        opacity: .7;
        cursor: wait;
    }

    .auth-cta:focus-visible,
    .auth-link:focus-visible {
        outline: 3px solid var(--brass);
        outline-offset: 2px;
    }

    .auth-link {
        color: var(--teal-deep);
        font-weight: 700;
    }

    .auth-link:hover {
        text-decoration: underline;
    }

    /* Existing registration form is built with Tailwind teal-700 buttons; align them */
    #regSubmitBtn {
        background: var(--teal) !important;
        border-radius: 12px !important;
        box-shadow: none !important;
        transform: none !important;
    }

    #regSubmitBtn:hover {
        background: var(--teal-deep) !important;
    }
</style>

