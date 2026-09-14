<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureHeadOfficer
{
    /**
     * Handle an incoming request to restrict access to Unit Heads & Directorate Command.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user || !$user->is_head || !$user->is_approved) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Unauthorized command action.'], 403);
            }
            return redirect('/cases')->with('error', 'Unauthorized access: Restricted to Unit Command & Directorate Heads.');
        }

        return $next($request);
    }
}
