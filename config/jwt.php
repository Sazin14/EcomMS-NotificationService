<?php

return [

    'algo' => env('JWT_ALGO', 'RS256'),

    // Copy the PUBLIC key of AuthService here (the same file the other services use).
    'public_key_path' => env('JWT_PUBLIC_KEY_PATH', 'storage/keys/jwt-public.pem'),

];
