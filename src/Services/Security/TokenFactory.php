<?php

namespace App\Services\Security;

class TokenFactory
{
    public function createPlainToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
