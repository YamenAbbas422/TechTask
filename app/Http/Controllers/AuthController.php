<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

/**
 * Class AuthController
 *
 * Handles authentication and user registration.
 *
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{
    use MessageTrait;

    /**
     * Register a new user and create a tenant.
     *
     * @param \Illuminate\Http\Request $request The request containing user registration data.
     * @return \Illuminate\Http\JsonResponse The registered user with an access token.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'tenant_name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|same:password',
        ]);

        $tenant = Tenant::create([
            'name' => $request->tenant_name,
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenant->id,
        ]);

        $success['token'] =  $user->createToken('authToken')->accessToken;
        $success['name'] =  $user->name;
        return $this->sendResponse($success, 'User register successfully.');
    }

    /**
     * Authenticate a user and generate an access token.
     *
     * @param \Illuminate\Http\Request $request The request containing login credentials.
     * @return \Illuminate\Http\JsonResponse The authenticated user with an access token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] =  $user->createToken('authToken')->accessToken;
            $success['name'] =  $user->name;

            return $this->sendResponse($success, 'User login successfully.');
        } else {

            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised'], 401);
        }
    }

    /**
     * Logout a user and revoke their access token.
     *
     * @param \Illuminate\Http\Request $request The request from the authenticated user.
     * @return \Illuminate\Http\JsonResponse Success message.
     */
    public function destroy(Request $request)
    {
        if (Auth::user()) {
            $request->user()->token()->revoke();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'No user authenticated.',
        ], 401);
    }
}
