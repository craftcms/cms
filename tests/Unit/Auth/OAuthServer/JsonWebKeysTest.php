<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuthServer\JsonWebKeys;
use Firebase\JWT\JWK;

it('identifies keys by their RFC 7638 thumbprint', function (): void {
    $key = JWK::parseKey([
        'kty' => 'RSA',
        'n' => '0vx7agoebGcQSuuPiLJXZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86zwu1RK7aPFFxuhDR1L6tSoc_BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ_2W-5JsGY4Hc5n9yBXArwl93lqt7_RN5w6Cf0h4QyQ5v-65YGjQR0_FDW2QvzqY368QQMicAtaSqzs8KJZgnYb9c7d0zgdAZHzu6qMQvRL5hajrn1n91CbOpbISD08qNLyrdkt-bFTWhAI4vMQFh6WeZu0fM4lFd2NcRwr3XPksINHaQ-G_xBniIqbw0Ls1jF44-csFCur-kEgU8awapJzKnqDKgw',
        'e' => 'AQAB',
    ], 'RS256')->getKeyMaterial();

    expect(JsonWebKeys::keyId($key))->toBe('NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs');
});
