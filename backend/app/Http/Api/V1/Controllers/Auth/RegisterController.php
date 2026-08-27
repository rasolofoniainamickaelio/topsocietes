<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers\Auth;

use App\Domain\Auth\Actions\RegisterAction;
use App\Domain\Auth\Data\RegisterData;
use App\Http\Api\V1\Requests\RegisterRequest;
use App\Http\Api\V1\Resources\UserResource;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterAction $action): UserResource
    {
        return UserResource::make($action->execute(RegisterData::from($request->validated())));
    }
}
