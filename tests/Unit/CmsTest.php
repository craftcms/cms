<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Updates;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Context::forgetHidden('craft.info');
    Context::forgetHidden('craft.isInstalled');
});

function createInfoTable(): void
{
    Schema::dropIfExists(Table::INFO);

    Schema::create(Table::INFO, function (Blueprint $table) {
        $table->integer('id', true);
        $table->string('version', 50);
        $table->string('schemaVersion', 15);
        $table->char('configVersion', 12)->default('000000000000');
        $table->dateTime('dateCreated')->nullable();
        $table->dateTime('dateUpdated')->nullable();
        $table->char('uid', 36)->default('0');
    });
}

function createInfoRow(string $uid = 'system-uid'): void
{
    createInfoTable();

    Info::create([
        'id' => 1,
        'version' => Cms::VERSION,
        'schemaVersion' => Cms::SCHEMA_VERSION,
        'configVersion' => 'abcdefghijkl',
        'uid' => $uid,
    ]);
}

it('exposes Craft version constants', function () {
    expect(Cms::NAME)->toBe('Craft CMS')
        ->and(Cms::VERSION)->toBeString()
        ->and(Cms::SCHEMA_VERSION)->toBeString()
        ->and(Cms::MIN_VERSION_REQUIRED)->toBeString();
});

it('resolves the general config from the container', function () {
    expect(Cms::config())->toBeInstanceOf(GeneralConfig::class)
        ->and(Cms::config())->toBe(app(GeneralConfig::class));
});

it('uses the logged-in CP user timezone preference first', function () {
    Cms::config()->cpTrigger = 'admin';
    app()->instance('request', Request::create('/admin'));

    Auth::shouldReceive('hasUser')->andReturnTrue()
        ->shouldReceive('id')->once()->andReturn(42);
    Users::shouldReceive('getUserPreference')->once()->with(42, 'timeZone')->andReturn('Europe/Brussels');

    expect(Cms::timezone())->toBe('Europe/Brussels');
});

it('falls back through configured timezone sources', function (?string $configTimezone, ?string $projectConfigTimezone, string $appTimezone, string $expected) {
    Cms::config()->cpTrigger = 'admin';
    Cms::config()->timezone = $configTimezone;
    Config::set('app.timezone', $appTimezone);
    app()->instance('request', Request::create('/site'));

    ProjectConfig::shouldReceive('get')->with('system.timeZone')->andReturn($projectConfigTimezone);

    expect(Cms::timezone())->toBe($expected);
})->with([
    'general config' => ['Europe/Amsterdam', 'Europe/Brussels', 'UTC', 'Europe/Amsterdam'],
    'project config' => [null, 'Europe/Brussels', 'UTC', 'Europe/Brussels'],
    'app config' => [null, null, 'America/New_York', 'America/New_York'],
]);

it('parses environment variables in the configured timezone', function () {
    $_SERVER['CRAFT_TEST_TIMEZONE'] = 'Europe/Paris';
    Cms::config()->timezone = '$CRAFT_TEST_TIMEZONE';
    app()->instance('request', Request::create('/site'));

    expect(Cms::timezone())->toBe('Europe/Paris');

    unset($_SERVER['CRAFT_TEST_TIMEZONE']);
});

it('falls back to UTC when ICU rejects the timezone', function () {
    Cms::config()->timezone = 'Not/A_Timezone';
    app()->instance('request', Request::create('/site'));

    expect(Cms::timezone())->toBe('UTC');
});

it('uses the accepted language while Craft is not installed', function () {
    Cms::setIsInstalled(false);
    I18N::shouldReceive('getAppLocaleIds')->once()->andReturn(collect(['fr', 'en']));

    $request = Request::create('/admin', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-CA,fr;q=0.8,en;q=0.6']);

    expect(Cms::targetLanguage($request))->toBe('fr');
});

it('uses the accepted language while a Craft update is pending', function () {
    Cms::setIsInstalled();
    Updates::shouldReceive('isCraftUpdatePending')->once()->andReturn(true);
    I18N::shouldReceive('getAppLocaleIds')->once()->andReturn(collect(['de', 'en']));

    $request = Request::create('/admin', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-CH,de;q=0.8,en;q=0.6']);

    expect(Cms::targetLanguage($request))->toBe('de');
});

it('uses the current site language for site requests', function () {
    Cms::setIsInstalled();
    Updates::shouldReceive('isCraftUpdatePending')->once()->andReturn(false);
    Sites::shouldReceive('getCurrentSite')->once()->andReturn(new Site(['language' => 'nl-BE']));

    expect(Cms::targetLanguage(Request::create('/news')))->toBe('nl-BE');
});

it('uses a valid CP user language preference', function () {
    Cms::setIsInstalled();
    Cms::config()->cpTrigger = 'admin';
    Updates::shouldReceive('isCraftUpdatePending')->once()->andReturn(false);
    $user = Mockery::mock(CraftUser::class);
    $user->shouldReceive('getAuthIdentifier')->once()->andReturn(42);
    Auth::shouldReceive('craftUser')->once()->andReturn($user);
    Users::shouldReceive('getUserPreference')->once()->with(42, 'language')->andReturn('pt-BR');
    I18N::shouldReceive('validateAppLocaleId')->once()->with('pt-BR')->andReturn(true);

    expect(Cms::targetLanguage(Request::create('/admin')))->toBe('pt-BR');
});

it('falls back to the configured default CP language', function () {
    Cms::setIsInstalled();
    Cms::config()->cpTrigger = 'admin';
    Cms::config()->defaultCpLanguage = 'es';
    Updates::shouldReceive('isCraftUpdatePending')->once()->andReturn(false);
    Auth::shouldReceive('craftUser')->once()->andReturnNull();

    expect(Cms::targetLanguage(Request::create('/admin')))->toBe('es');
});

it('falls back to the accepted language when the CP user preference is invalid and no default is configured', function () {
    Cms::setIsInstalled();
    Cms::config()->cpTrigger = 'admin';
    Cms::config()->defaultCpLanguage = null;
    Updates::shouldReceive('isCraftUpdatePending')->once()->andReturn(false);
    $user = Mockery::mock(CraftUser::class);
    $user->shouldReceive('getAuthIdentifier')->once()->andReturn(42);
    Auth::shouldReceive('craftUser')->once()->andReturn($user);
    Users::shouldReceive('getUserPreference')->once()->with(42, 'language')->andReturn('not-real');
    I18N::shouldReceive('validateAppLocaleId')->once()->with('not-real')->andReturn(false);
    I18N::shouldReceive('getAppLocaleIds')->once()->andReturn(collect(['it', 'en']));

    $request = Request::create('/admin', server: ['HTTP_ACCEPT_LANGUAGE' => 'it-IT,it;q=0.8,en;q=0.6']);

    expect(Cms::targetLanguage($request))->toBe('it');
});

it('uses the project config system name first', function () {
    ProjectConfig::shouldReceive('get')->once()->with('system.name')->andReturn('Project Name');

    expect(Cms::systemName())->toBe('Project Name');
});

it('falls back to the primary site system name', function () {
    ProjectConfig::shouldReceive('get')->once()->with('system.name')->andReturn(null);
    Sites::shouldReceive('getPrimarySite')->once()->andReturn(new Site(['name' => 'Primary Site']));

    expect(Cms::systemName())->toBe('Primary Site');
});

it('falls back to the app name when there is no configured system or primary site name', function () {
    ProjectConfig::shouldReceive('get')->once()->with('system.name')->andReturn(null);
    Sites::shouldReceive('getPrimarySite')->once()->andThrow(new SiteNotFoundException);
    Config::set('app.name', 'Laravel App');

    expect(Cms::systemName())->toBe('Laravel App');
});

it('returns the system UID from the info row', function () {
    createInfoRow('test-system-uid');

    expect(Cms::systemUid())->toBe('test-system-uid');
});

it('returns and caches the installed state from the info row', function () {
    createInfoRow();

    expect(Cms::isInstalled())->toBeTrue();

    Schema::drop(Table::INFO);

    expect(Cms::isInstalled())->toBeTrue();
});

it('can force the installed state cache', function () {
    Cms::setIsInstalled(false);

    expect(Cms::isInstalled())->toBeFalse();

    Cms::setIsInstalled();

    expect(Cms::isInstalled())->toBeTrue();
});

it('performs a strict installed check against the database', function () {
    createInfoTable();
    Cms::setIsInstalled();

    expect(Cms::isInstalled(true))->toBeFalse();

    createInfoRow();

    expect(Cms::isInstalled(true))->toBeTrue();
});

it('returns false when the database connection fails during installed checks', function () {
    DB::shouldReceive('connection')->once()->andThrow(new PDOException('connection failed'));

    expect(Cms::isInstalled())->toBeFalse()
        ->and(Context::getHidden('craft.isInstalled'))->toBeFalse();
});

it('logs and returns false when fetching the info row fails', function () {
    createInfoTable();
    Log::shouldReceive('error')->once();

    expect(Cms::isInstalled())->toBeFalse()
        ->and(Context::getHidden('craft.isInstalled'))->toBeFalse();
});
