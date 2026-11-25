<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the application registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming partner registration request.
     * Public registration ALWAYS forces role=partner and status=pending.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', Password::defaults()],
            'company_name'          => ['required', 'string', 'max:255'],
            'phone'                 => ['required', 'string', 'max:30'],
            'city'                  => ['nullable', 'string', 'max:100'],
            'state'                 => ['nullable', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($request) {
            // Strictly hardcode Partner role and Pending status
            $newUser = User::create([
                'name'     => $request->name,
                'email'    => strtolower(trim($request->email)),
                'password' => Hash::make($request->password),
                'role'     => UserRole::Partner,
                'status'   => UserStatus::Pending,
            ]);

            $partnerCode = 'TGP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(2)));

            Partner::create([
                'user_id'                 => $newUser->id,
                'partner_code'            => $partnerCode,
                'company_name'            => $request->company_name,
                'phone'                   => $request->phone,
                'city'                    => $request->city,
                'state'                   => $request->state,
                'country'                 => 'India',
                'default_commission_rate' => 10.00,
            ]);

            return $newUser;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('partner.pending')->with('success', 'Your partner registration has been submitted and is currently pending review by Tech Googly administrators.');
    }
}
