<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\Utility\Events\RegisterUtilities;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use CraftCms\Cms\Utility\Utilities\SystemMessages;
use CraftCms\Cms\Utility\Utilities\SystemReport;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->utilities = app(Utilities::class);
});

it('can get all utility types', function () {
    expect($this->utilities->getAllUtilityTypes())->not()->toBeEmpty();
});

it('contains system messages when craft is pro', function () {
    Edition::set(Edition::Solo);

    expect($this->utilities->getAllUtilityTypes())->not()->toContain(SystemMessages::class);

    Edition::set(Edition::Pro);

    expect($this->utilities->getAllUtilityTypes())->toContain(SystemMessages::class);
});

it('does not contains assetIndexes utility when there are no volumes', function () {
    expect($this->utilities->getAllUtilityTypes())->not()->toContain(AssetIndexes::class);
});

it('can register extra utilities', function () {
    expect($this->utilities->getAllUtilityTypes())->not()->toContain(DummyUtility::class);

    Event::listen(RegisterUtilities::class, function (RegisterUtilities $event) {
        $event->types[] = DummyUtility::class;
    });

    expect($this->utilities->getAllUtilityTypes())->toContain(DummyUtility::class);
});

it('can get authorized utility types', function () {
    expect($this->utilities->getAuthorizedUtilityTypes())->toBeEmpty();

    actingAs(User::first());

    expect($this->utilities->getAuthorizedUtilityTypes())->not()->toBeEmpty();
});

test('disabled utilities are not included', function () {
    actingAs(User::first());

    expect($this->utilities->getAuthorizedUtilityTypes())->toContain(SystemReport::class);

    Cms::config()->disabledUtilities[] = 'system-report';

    expect($this->utilities->getAuthorizedUtilityTypes())->not()->toContain(SystemReport::class);
});

it('can get badge count for all utilities', function () {
    actingAs(User::first());

    expect($this->utilities->getUtilitiesBadgeCount())->toBe(0);
});

class DummyUtility extends Utility
{
    #[\Override]
    public static function displayName(): string
    {
        return 'Dummy';
    }

    #[\Override]
    public static function id(): string
    {
        return 'dummy';
    }

    #[\Override]
    public static function contentHtml(): string
    {
        return '';
    }
}
