<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    /**
     * Display the authenticated user details.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load('type');

        return response()->json($user->makeHidden(['password', 'remember_token']));
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'phone', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'state_region_id' => 'nullable|exists:state_regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'township_id' => 'nullable|exists:townships,id',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'active' => 'boolean',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }

    /**
     * Get the user's associated type.
     */
    public function userType(Request $request)
    {
        $type = $request->user()->type;

        return response()->json([
            'id' => $type->id ?? null,
            'name' => $type->name ?? null
        ]);
    }

    /**
     * Get user's location details (State, District, Township).
     */
    public function location(Request $request)
    {
        $user = $request->user()->load(['stateRegion', 'district', 'township']);

        return response()->json([
            'state_region' => $user->stateRegion ? $user->stateRegion->name : null,
            'district' => $user->district ? $user->district->name : null,
            'township' => $user->township ? $user->township->name : null,
        ]);
    }
}
