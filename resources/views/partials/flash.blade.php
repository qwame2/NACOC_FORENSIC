@if (session('status') || session('success') || session('error') || session('warning') || session('info') || (isset($errors) && $errors->any()))
    @php
        $statusMsg = session('status') ?? session('success') ?? session('info');
        $errorMsg = session('error') ?? (isset($errors) && $errors->any() ? $errors->first() : null);
        $warningMsg = session('warning');
        $msg = $errorMsg ?? $statusMsg ?? $warningMsg;
        $isErrorOrWarning = !empty($errorMsg) || !empty($warningMsg);
        
        $title = 'System Notification';
        if (!empty($errorMsg)) {
            if (stripos($errorMsg, 'password') !== false || stripos($errorMsg, 'username') !== false || stripos($errorMsg, 'login') !== false || stripos($errorMsg, 'credential') !== false || stripos($errorMsg, 'wrong') !== false || stripos($errorMsg, 'Approval') !== false) {
                $title = 'Authentication Failure';
            } else {
                $title = 'Action Failed';
            }
        } elseif (!empty($warningMsg)) {
            $title = 'System Warning';
        } elseif (stripos($msg, 'Registration') !== false) {
            $title = 'Registration Successful';
        } elseif (stripos($msg, 'logged out') !== false || stripos($msg, 'logout') !== false) {
            $title = 'Officer Logged Out Successfully';
        } elseif (stripos($msg, 'Login') !== false) {
            $title = 'Login Successful';
        }
    @endphp

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 99999;">
        <div id="topRightFlashToast" class="toast align-items-center text-white {{ $isErrorOrWarning ? 'bg-danger' : 'bg-success' }} border-0 shadow-lg show fade" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex align-items-center">
                <div class="toast-body py-3 px-3">
                    <div class="fw-bold font-monospace text-uppercase small mb-1" style="font-size: 11px;">
                        <span class="spinner-grow spinner-grow-sm me-1" style="width: 8px; height: 8px;" role="status"></span>
                        {{ $title }}
                    </div>
                    <div class="small leading-tight">
                        {{ $msg }}
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="dismissTopRightFlashToast()" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        function dismissTopRightFlashToast() {
            const toast = document.getElementById('topRightFlashToast');
            if (toast) {
                toast.classList.remove('show');
                setTimeout(() => {
                    const container = toast.closest('.toast-container');
                    if (container) {
                        container.remove();
                    }
                }, 300);
            }
        }

        setTimeout(dismissTopRightFlashToast, 6000);
    </script>
@endif

