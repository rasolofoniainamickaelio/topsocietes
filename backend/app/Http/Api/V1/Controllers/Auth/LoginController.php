<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers\Auth;

use App\Domain\Auth\Actions\LoginAction;
use App\Domain\Auth\Data\LoginData;
use App\Http\Api\V1\Requests\LoginRequest;
use App\Http\Api\V1\Resources\UserResource;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, LoginAction $action): UserResource
    {
        $user = $action->execute(LoginData::from($request->validated()));

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        return UserResource::make($user);
    }
}
