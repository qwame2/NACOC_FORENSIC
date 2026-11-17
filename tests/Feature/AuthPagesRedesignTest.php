<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_new_shell_and_keeps_script_hooks(): void
    {
        $html = $this->get('/login')->assertStatus(200)->getContent();

        foreach (['auth-shell', 'auth-brand', 'id="loginForm"', 'id="serviceId"', 'id="passwordInput"',
            'id="submitBtn"', 'id="btnText"', 'id="eyeIcon"', 'id="loginInlineError"',
            'id="loginAccountApprovedPopoverModal"', 'id="toastNotification"', 'id="liveClock"'] as $needle) {
            $this->assertStringContainsString($needle, $html, "login missing {$needle}");
        }
    }

    public function test_register_page_renders_new_shell_and_keeps_form_ids(): void
    {
        $html = $this->get('/register')->assertStatus(200)->getContent();

        foreach (['auth-shell--wide', 'id="registerForm"', 'id="fullname"', 'id="username"', 'id="password"',
            'id="confirm_password"', 'id="regSubmitBtn"', 'id="registerErrorBox"', 'id="liveClock"',
            'id="termsCheckbox"'] as $needle) {
            $this->assertStringContainsString($needle, $html, "register missing {$needle}");
        }
        $this->assertSame(1, substr_count($html, '</body>'));
    }
}
