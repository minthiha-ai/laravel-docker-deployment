<?php

namespace App\Http\Controllers\Api;

use App\Models\BusinessAdminAccount;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Mockery\Exception;

class AuthController extends Controller
{
    use ResponseTrait;

    public function admin_register(Request $request)
    {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:business_admin_accounts',
                'phone' => 'required|string|max:15|unique:business_admin_accounts',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = BusinessAdminAccount::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'phone_verified_at' => null,
                'otp' => null,
                'otp_expires_at' => null
            ]);

        return $this->success('Register successful. Please verify your phone.', [], 201);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'type_id' => 1,
            'phone_verified_at' => null,
            'otp' => null,
            'otp_expires_at' => null
        ]);

        return $this->success('Register successful. Please verify your phone.', [], 201);
    }

    public function requestOtp(Request $request,$type)
    {

        $request->validate([
            'phone' => 'required|string|max:15',
        ]);
        if($type == 'admin'){
            $user = BusinessAdminAccount::where('phone', $request->phone)->first();
        }else {
            $user = User::where('phone', $request->phone)->first();
        }
        if ($user) {
            if ($user->phone_verified_at) {
                return $this->fail('Phone already verified.', [], 400);
            }

            if ($user->otp_expires_at && Carbon::parse($user->otp_expires_at)->isFuture()) {
                return $this->fail('OTP already sent. Please wait before requesting again.', [], 429);
            }

            $otp = random_int(100000, 999999);

            $user->update([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(5),
            ]);

            return $this->success("Demo OTP: {$otp}. Use it within 5 minutes.", []);
        }else{
            return $this->fail('User Not Found.', 404,[]);
        }
    }

    public function verifyOtp(Request $request , $type)
    {
        $request->validate([
            'phone' => 'required|string|max:15',
            'otp' => 'required|digits:6',
        ]);

       if($type == 'admin') {
           $user = BusinessAdminAccount::where('phone',$request->phone)->first();
       }else {
           $user = User::where('phone', $request->phone)->first();
       }
        if($user){
            if (!$user->otp || $user->otp !== $request->otp) {
                return $this->fail('Invalid OTP.', 400,[]);
            }

            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return $this->fail('OTP has expired. Request a new one.', 400,[]);
            }

            $user->update([
                'phone_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);
            return $this->success('Phone verified successfully.', []);
        }else{

            return $this->fail('User Not Found.', 404,[]);
        }

    }

    public function user_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? ['email' => $request->login]
            : ['phone' => $request->login];

        $credentials['password'] = $request->password;

        if (!Auth::attempt($credentials)) {
            return $this->fail('Invalid credentials.', [], 401);
        }

        $user = Auth::user();

        if (!$user->phone_verified_at) {
            return $this->fail('Phone not verified. Please verify first.', [], 403);
        }

        $data = [
            'token' => $user->createToken('authToken')->accessToken,
            'user' => $user
        ];

        return $this->success('Login successful.', $data, 200);
    }

    public function admin_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = BusinessAdminAccount::where($field, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->fail('Invalid credentials.', 401,[]);
        }

        if (!$user->phone_verified_at) {
            return $this->fail('Phone not verified. Please verify first.', 403,[]);
        }

        $data = [
            'token' => $user->createToken('authToken')->accessToken,
            'user' => $user
        ];

        return $this->success('Login successful.', $data, 200);
    }


    public function user(Request $request)
    {
        return $this->success('User retrieved successfully.', $request->user()->load('type'), 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->success('Logged out successfully.', [], 200);
    }
}
