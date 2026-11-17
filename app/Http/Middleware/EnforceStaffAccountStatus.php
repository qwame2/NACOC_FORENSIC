<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Monitors authenticated staff officer approval state:
 * - Blocks non-GET / API mutations with 403 while a staff account is revoked.
 * - Injects the blurred-background revocation popover overlay & real-time watchdog into HTML pages.
 */
class EnforceStaffAccountStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawUser = Auth::user();
        if (!$rawUser) {
            return $next($request);
        }

        $user = $rawUser->fresh() ?? $rawUser;

        // Only applies to non-head staff officers
        if ($user->is_head) {
            return $next($request);
        }

        $isExcludedPath = $request->is('logout')
            || $request->is('api/logout')
            || $request->is('api/auth/account-status');

        $isRevoked = !$user->is_approved;

        if ($isRevoked && !$isExcludedPath) {
            $isMutationOrApi = !$request->isMethod('GET')
                || $request->ajax()
                || $request->expectsJson()
                || $request->wantsJson()
                || $request->is('api/*');

            if ($isMutationOrApi) {
                $message = 'Your staff account has been revoked. Please contact your Head of Unit to reactivate your account.';
                if ($request->ajax() || $request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'revoked' => true,
                        'message' => $message,
                    ], 403);
                }

                return response($message, 403);
            }
        }

        // Enforce Create New Cases permission for non-head staff officers
        $unitOrPathContext = (string) ($request->input('unit_name')
            ?: $request->input('from')
            ?: $request->path());

        $editOrAssociateContext = (string) ($request->input('from')
            ?: $request->input('unit_name')
            ?: $request->path());

        $permissionChecks = [
            [
                'matched' => $request->is('*/staff/create*')
                    || $request->is('staff/create*')
                    || $request->is('*/create')
                    || $request->is('cases/create*')
                    || ($request->isMethod('POST') && $request->is('cases')),
                'permission' => 'create_cases',
                'context' => $unitOrPathContext,
                'message' => 'Access Restricted: Your Head of Unit has disabled your Create New Cases permission.',
            ],
            [
                'matched' => $request->is('cases/*/edit*')
                    || $request->is('*/cases/*/edit*')
                    || (($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH'))
                        && ($request->is('cases/*/update*') || $request->is('*/cases/*/update*'))),
                'permission' => 'edit_cases',
                'context' => $editOrAssociateContext,
                'message' => 'Access Restricted: Your Head of Unit has disabled your Edit Case & Suspects permission.',
            ],
            [
                'matched' => $request->is('cases/*/associate*')
                    || $request->is('*/cases/*/associate*'),
                'permission' => 'add_associate',
                'context' => $editOrAssociateContext,
                'message' => 'Access Restricted: Your Head of Unit has disabled your Add Associate permission.',
            ],
            [
                'matched' => $request->is('*/staff/drug-screening*')
                    || $request->is('staff/drug-screening*')
                    || $request->is('*/drug-screening*')
                    || $request->is('drug-screening*'),
                'permission' => 'record_lab_analysis',
                'context' => $unitOrPathContext,
                'message' => 'Access Restricted: Your Head of Unit has disabled your Lab & Drug Screening permission.',
            ],
            [
                'matched' => $request->is('*/vault*')
                    || $request->is('vault*')
                    || $request->is('*/exhibit-vault*')
                    || $request->is('exhibit-vault*'),
                'permission' => 'manage_exhibits',
                'context' => $unitOrPathContext,
                'message' => 'Access Restricted: Your Head of Unit has disabled your Manage Exhibit Vault permission.',
            ],
            [
                'matched' => $request->is('*/staff/report*')
                    || $request->is('staff/report*')
                    || $request->is('*/lab_sitrep_pdf*')
                    || $request->is('lab_sitrep_pdf*')
                    || $request->is('*/report_pdf*')
                    || $request->is('*/report/pdf*'),
                'permission' => 'generate_reports',
                'context' => $unitOrPathContext,
                'message' => 'Access Restricted: Your Head of Unit has disabled your Generate Reports & PDFs permission.',
            ],
        ];

        foreach ($permissionChecks as $check) {
            if (
                !$isRevoked
                && $check['matched']
                && method_exists($user, 'hasPermission')
                && !$user->hasPermission($check['permission'], $check['context'])
            ) {
                return $this->denyPermissionResponse($request, $user, $check['permission'], $check['message']);
            }
        }

        $response = $next($request);

        if ($isExcludedPath || !$request->isMethod('GET') || $request->ajax()) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        $content = $response->getContent();

        if (
            is_string($content)
            && str_contains(strtolower($contentType), 'text/html')
            && str_contains($content, '</body>')
        ) {
            $overlayHtml = view('partials.account_revoked_overlay', [
                'revokedUser' => $user,
                'isInitiallyRevoked' => $isRevoked,
            ])->render();

            $pos = strrpos($content, '</body>');
            if ($pos !== false) {
                $originalContent = property_exists($response, 'original') ? $response->original : null;
                $response->setContent(
                    substr($content, 0, $pos) . "\n" . $overlayHtml . "\n" . substr($content, $pos)
                );
                // Preserve rendered View instance so Laravel test assertions (assertViewIs/viewData) stay intact.
                if ($originalContent instanceof \Illuminate\Contracts\View\View && property_exists($response, 'original')) {
                    $response->original = $originalContent;
                }
            }
        }

        return $response;
    }

    /**
     * Return a uniform 403 JSON response or unit redirect with flash error when a staff permission is disabled.
     */
    private function denyPermissionResponse(Request $request, $user, string $permissionKey, string $permMessage): Response
    {
        if ($request->ajax() || $request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'permission_denied' => $permissionKey,
                'message' => $permMessage,
            ], 403);
        }

        $unitSlug = \App\Enums\ForensicUnit::resolveOperationalSlug($user->unit);
        $fallbackCaseUrl = match ($unitSlug) {
            'lab' => '/Lab_Analysis/staff/case',
            'em'  => '/Evidence_Management/staff/case',
            'dpf' => '/Digital_Forensics/staff/case',
            default => '/Crime_Scene_Support/staff/case',
        };

        return redirect($fallbackCaseUrl)->with('error', $permMessage);
    }
}

