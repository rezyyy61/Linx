<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegisterService;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    public function __construct(private RegisterService $register) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->register->register($request, $data);

        return response()->json([
            'message' => 'registered',
            'user' => $result['user'],
        ], (int) $result['http']);
    }
}
