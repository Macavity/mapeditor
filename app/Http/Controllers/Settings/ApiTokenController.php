<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

class ApiTokenController extends Controller
{
    public function index()
    {
        return inertia('settings/ApiTokens', [
            'tokens' => auth()->user()->tokens
        ]);
    }
}
