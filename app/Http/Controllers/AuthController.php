<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = Str::lower($d['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.']);
        }
        if (! Auth::attempt($d + ['active' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
        }
        if (! Auth::user()->clinic?->active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Akses klinik tidak aktif.']);
        }
        RateLimiter::clear($key);
        $r->session()->regenerate();
        $r->session()->put('last_activity', time());
        LoginHistory::create(['clinic_id' => Auth::user()->clinic_id, 'user_id' => Auth::id(), 'ip' => $r->ip()]);
        Audit::record(Auth::user(), 'auth.login', Auth::user());

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/login');
    }

    public function forgot(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        Password::sendResetLink($r->only('email'));

        return back()->with('status', 'Jika akun terdaftar, tautan reset telah dikirim.');
    }

    public function reset(Request $r)
    {
        $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|confirmed|min:12']);
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect('/login')->with('status', 'Kata sandi diperbarui. Silakan masuk.');
    }
}
