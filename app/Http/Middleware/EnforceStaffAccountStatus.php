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
        $isCreateCaseRequest = $request->is('*/staff/create*')
            || $request->is('staff/create*')
            || $request->is('*/create')
            || $request->is('cases/create*')
            || ($request->isMethod('POST') && $request->is('cases'));

        $unitOrPathContext = (string) ($request->input('unit_name')
            ?: $request->input('from')
            ?: $request->path());

        if (
            !$isRevoked
            && $isCreateCaseRequest
            && method_exists($user, 'hasPermission')
            && !$user->hasPermission('create_cases', $unitOrPathContext)
        ) {
            $permMessage = 'Access Restricted: Your Head of Unit has disabled your Create New Cases permission.';
            if ($request->ajax() || $request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'permission_denied' => 'create_cases',
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
                $response->setContent(
                    substr($content, 0, $pos) . "\n" . $overlayHtml . "\n" . substr($content, $pos)
                );
            }
        }

        return $response;
    }
}

