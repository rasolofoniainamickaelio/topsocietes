<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers\Auth;

use App\Http\Api\V1\Resources\UserResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
