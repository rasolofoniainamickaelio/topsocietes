<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Data\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginAction
{
    public function execute(LoginData $data): ?User
    {
        if (! Auth::attempt(['email' => $data->email, 'password' => $data->password])) {
            return null;
        }

        Session::regenerate();

        /** @var User */
        return Auth::user();
    }
}
