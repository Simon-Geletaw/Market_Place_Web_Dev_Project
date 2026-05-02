<?php

declare(strict_types=1);

final class AuthController
{
    public function showLogin(): array
    {
        return success_response('Auth login route ready.');
    }

    public function login(): array
    {
        return success_response('Auth login action ready.');
    }

    public function showRegister(): array
    {
        return success_response('Auth register route ready.');
    }

    public function register(): array
    {
        return success_response('Auth register action ready.');
    }

    public function logout(): array
    {
        return success_response('Auth logout action ready.');
    }
}
