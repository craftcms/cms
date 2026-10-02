<?php

declare(strict_types=1);

use craft\base\RequestTrait;
use CraftCms\Aliases\Aliases;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;

test('sets the legacy web alias using the configured precedence', function(
    ?string $craftWebUrl,
    ?string $configuredAlias,
    string $appUrl,
    string $expected,
    bool $dynamic,
): void {
    $originalEnvironmentValue = getenv('CRAFT_WEB_URL');
    $originalServerValue = $_SERVER['CRAFT_WEB_URL'] ?? null;
    $originalEnvValue = $_ENV['CRAFT_WEB_URL'] ?? null;
    $originalAlias = Aliases::get('@web', false);
    $originalAppUrl = Config::get('app.url');

    try {
        unset($_SERVER['CRAFT_WEB_URL'], $_ENV['CRAFT_WEB_URL']);
        putenv('CRAFT_WEB_URL');
        if ($craftWebUrl !== null) {
            putenv("CRAFT_WEB_URL={$craftWebUrl}");
        }
        Env::enablePutenv();

        Aliases::remove('@web');
        if ($configuredAlias !== null) {
            Aliases::set('@web', $configuredAlias);
        }

        Config::set('app.url', $appUrl);

        $request = new WebAliasRequest();
        $request->initializeWebAlias('/');

        expect(Aliases::get('@web'))->toBe($expected)
            ->and($request->isWebAliasSetDynamically)->toBe($dynamic);
    } finally {
        putenv('CRAFT_WEB_URL');
        if ($originalEnvironmentValue !== false) {
            putenv("CRAFT_WEB_URL={$originalEnvironmentValue}");
        }
        if ($originalServerValue === null) {
            unset($_SERVER['CRAFT_WEB_URL']);
        } else {
            $_SERVER['CRAFT_WEB_URL'] = $originalServerValue;
        }
        if ($originalEnvValue === null) {
            unset($_ENV['CRAFT_WEB_URL']);
        } else {
            $_ENV['CRAFT_WEB_URL'] = $originalEnvValue;
        }
        Env::enablePutenv();

        Aliases::remove('@web');
        if ($originalAlias !== false) {
            Aliases::set('@web', $originalAlias);
        }

        Config::set('app.url', $originalAppUrl);
    }
})->with([
    'Craft URL overrides configured alias' => [
        'https://env.example.test',
        'https://configured.example.test',
        'https://app.example.test',
        'https://env.example.test',
        false,
    ],
    'configured alias overrides app URL' => [
        '',
        'https://configured.example.test',
        'https://app.example.test',
        'https://configured.example.test',
        false,
    ],
    'app URL is the next fallback' => [
        null,
        null,
        'https://app.example.test',
        'https://app.example.test',
        false,
    ],
    'request fallback handles an empty app URL' => [
        null,
        null,
        '',
        '',
        true,
    ],
]);

class WebAliasRequest
{
    use RequestTrait;

    public function initializeWebAlias(string $fallback): void
    {
        $this->setWebAlias($fallback);
    }
}
