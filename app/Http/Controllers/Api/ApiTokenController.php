<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class ApiTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = $request->user()->createToken($request->name);

        return response()->json([
            'message' => 'API token created successfully.',
            'token' => $token->plainTextToken,
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return response()->json([
            'message' => 'API token revoked successfully.',
        ]);
    }
}
