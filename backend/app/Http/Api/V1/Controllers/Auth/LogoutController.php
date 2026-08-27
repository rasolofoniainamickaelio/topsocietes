<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers\Auth;

use App\Domain\Auth\Actions\LogoutAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __invoke(LogoutAction $action): Response
    {
        $action->execute();

        return response()->noContent();
    }
}
