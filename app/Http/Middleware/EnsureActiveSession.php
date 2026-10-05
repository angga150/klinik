<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            if (! Auth::user()->active || ! Auth::user()->clinic?->active || ($request->session()->has('last_activity') && time() - $request->session()->get('last_activity') > 1800)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Sesi berakhir. Silakan masuk kembali.');
            }
            // Background polling must not keep an unattended session alive.
            $poll = $request->is('livewire/*') && collect($request->input('components', []))->every(fn ($c) => collect($c['calls'] ?? [])->every(fn ($call) => ($call['method'] ?? '') === '$refresh'));
            if (! $poll) {
                $request->session()->put('last_activity', time());
            }
        }

        return $next($request);
    }
}
