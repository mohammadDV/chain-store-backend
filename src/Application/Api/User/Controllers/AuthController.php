<?php

namespace Application\Api\User\Controllers;

use Application\Api\User\Requests\LoginRequest;
use Application\Api\User\Requests\RegisterRequest;
use Application\Api\User\Requests\ForgotPasswordRequest;
use Application\Api\User\Requests\ResetPasswordRequest;
use Application\Api\User\Mail\PasswordResetMail;
use Application\Api\User\Requests\RegisterInformationRequest;
use Core\Http\Controllers\Controller;
use Domain\User\Models\User;
use Domain\User\Services\TelegramNotificationService;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Application\Api\User\Resources\UserResource;
use Domain\Wallet\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{

    /**
     * @param TelegramNotificationService $service
     */
    public function __construct(protected TelegramNotificationService $service)
    {

    }

    /**
     * Log in the user.
     */
    public function login(LoginRequest $request): Response
    {

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => __('site.These credentials do not match our records.'),
                'status' => 0
            ], 401);
        }

        $token = $user->createToken('chainstoretoken')->plainTextToken;

        return response([
            'is_admin' => $user->level == 3,
            'token' => $token,
            'verify_email' => !empty($user->email_verified_at),
            'verify_access' => !empty($user->verified_at),
            'customer_number' => $user->customer_number,
            'user' => new UserResource($user),
            'mesasge' => 'success',
            'status' => 1
        ], 200);
    }

    public function redirectToGoogle()
    {
        $query = http_build_query([
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/auth?' . $query);
    }

    /**
     * Log in the user.
     */
    public function handleGoogleCallback(Request $request)
    {

        $code = $request->input('code');

        if (!$code) {
            return redirect('/auth/check-verification');
        }

        // Exchange code for token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
        ]);

        $tokenData = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokenData['access_token'])
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        $payload = $userResponse->json();

        if ($payload) {

            $user = User::where('email', $payload['email'])->first();

            if (!empty($user->id)) {
                $user->createToken('chainstoretoken')->plainTextToken;
            } else {

                $nickname = str_replace(' ', '-', $payload['name']);

                $nickname = $this->nicknameCheck($nickname);
                $password = $nickname . '!@#' . rand(1111, 9999);

                $user = User::create([
                    'customer_number' => User::generateCustumerNumber(),
                    'role_id' => 2,
                    'status' => 1,
                    'email' => $payload['email'],
                    'google_id' => $payload['sub'],
                    'password' => bcrypt($password),
                    'email_verified_at' => now(),
                ]);

                $user->assignRole(['user']);

                $user->createToken('chainstoretoken')->plainTextToken;

                Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'currency' => Wallet::IRR,
                    'status' => 1
                ]);
            }
        }

        return redirect('/auth/check-verification');

    }

    /**
     * Check the nickname is unique or not
     * @param string $nickname
     * @return string $nickname
     */
    public function nicknameCheck(string $nickname): string
    {
        $user = User::query()
            ->where('nickname', $nickname)
            ->first();

        return !empty($user->id) ? $this->nicknameCheck($nickname . rand(111111, 999999)) : $nickname;
    }

    /**
     * Register the user.
     */
    public function completeRegister(RegisterInformationRequest $request): Response
    {

        Auth::user()->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'nickname' => $request->nickname,
            'mobile' => $request->mobile,
            'profile_photo_path' => $request->profile_photo_path,
            'verified_at' => now(),
        ]);

        return response([
            'user' => new UserResource(Auth::user()),
            'verify_email' => !empty(Auth::user()->email_verified_at),
            'verify_access' => !empty(Auth::user()->verified_at),
            'customer_number' => Auth::user()->customer_number,
            'status' => 1
        ], Response::HTTP_CREATED);
    }

    /**
     * Register the user.
     */
    public function register(RegisterRequest $request): Response
    {

        $user = User::create([
            'customer_number' => User::generateCustumerNumber(),
            'role_id' => 2,
            'status' => 1,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole(['user']);

        $token = $user->createToken('chainstoretoken')->plainTextToken;

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => Wallet::IRR,
            'status' => 1
        ]);

        event(new Registered($user));

        // $this->service->sendNotification(
        //     config('telegram.chat_id'),
        //     'ثبت نام کاربر جدید' . PHP_EOL .
        //     'email ' . $request->email . PHP_EOL
        // );

        // $this->service->sendNotification(
        //     config('telegram.chat_id'),
        //     'ثبت نام کاربر جدید' . PHP_EOL .
        //     'first_name ' . $request->first_name . PHP_EOL .
        //     'last_name ' . $request->last_name. PHP_EOL .
        //     'nickname ' . $request->nickname . PHP_EOL .
        //     'email ' . $request->email . PHP_EOL .
        //     'mobile ' . $request->mobile . PHP_EOL
        // );

        return response([
            'user' => new UserResource($user),
            'token' => $token,
            'verify_email' => !empty($user->email_verified_at),
            'verify_access' => !empty($user->verified_at),
            'customer_number' => $user->customer_number,
            'status' => 1
        ], Response::HTTP_CREATED);
    }

    /**
     * Log out the user.
     */
    public function logout(): Response
    {

        Auth::user()->tokens()->delete();
        return response([
            'mesasge' => 'success',
            'status' => 1
        ], 201);
    }

    /**
     * Log in the user.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => __('site.Invalid verification link')], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('site.Email already verified')], 200);
        }

        $user->markEmailAsVerified();
        event(new \Illuminate\Auth\Events\Verified($user));

        return redirect('/auth/check-verification');

    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'status' => 1,
                'message' => __('site.Already verified')
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'status' => 1,
            'message' => __('site.Verification link sent!')
        ]);
    }

    /**
     * Send password reset link to user's email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): Response
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response([
                'message' => __('site.We could not find a user with that email address.'),
                'status' => 0
            ], 404);
        }

        // Generate password reset token
        $token = Str::random(60);

        // Store the token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Generate reset URL (you can customize this based on your frontend URL)
        $resetUrl = config('app.frontend_url', 'http://localhost:3000') . '/auth/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        // Send email
        Mail::to($user->email)->send(new PasswordResetMail($user, $resetUrl));

        return response([
            'message' => __('site.Password reset link sent to your email.'),
            'status' => 1
        ], 200);
    }

    /**
     * Verify password reset token and return user info for reset form.
     */
    public function verifyResetToken(Request $request): Response
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email'
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response([
                'message' => __('site.Invalid reset token.'),
                'status' => 0
            ], 400);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response([
                'message' => __('site.Reset token has expired.'),
                'status' => 0
            ], 400);
        }

        // Verify token
        if (!Hash::check($request->token, $resetRecord->token)) {
            return response([
                'message' => __('site.Invalid reset token.'),
                'status' => 0
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        return response([
            'message' => __('site.Token is valid.'),
            'user' => [
                'email' => $user->email,
                'name' => $user->first_name . ' ' . $user->last_name
            ],
            'status' => 1
        ], 200);
    }

    /**
     * Reset user password with token.
     */
    public function resetPassword(ResetPasswordRequest $request): Response
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response([
                'message' => __('site.Invalid reset token.'),
                'status' => 0
            ], 400);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response([
                'message' => __('site.Reset token has expired.'),
                'status' => 0
            ], 400);
        }

        // Verify token
        if (!Hash::check($request->token, $resetRecord->token)) {
            return response([
                'message' => __('site.Invalid reset token.'),
                'status' => 0
            ], 400);
        }

        // Update user password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response([
            'message' => __('site.Password has been reset successfully.'),
            'status' => 1
        ], 200);
    }
}
