<?php

use Craft\Cms\User\Models\User;
use Craft\Cms\Utility\Events\RegisterUtilities;
use Craft\Cms\Utility\Utilities\AssetIndexes;
use Craft\Cms\Utility\Utilities\SystemMessages;
use Craft\Cms\Utility\Utilities\SystemReport;
use Craft\Cms\Utility\Utility;
use craft\enums\CmsEdition;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->utilities = app(\Craft\Cms\Utility\Utilities::class);
});

it('can get all utility types', function () {
    expect($this->utilities->getAllUtilityTypes())->not()->toBeEmpty();
});

it('contains system messages when craft is pro', function () {
    Craft::$app->setEdition(CmsEdition::Solo);

    expect($this->utilities->getAllUtilityTypes())->not()->toContain(SystemMessages::class);

    Craft::$app->setEdition(CmsEdition::Pro);

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

    Craft::$app->getConfig()->getGeneral()->disabledUtilities[] = 'system-report';

    expect($this->utilities->getAuthorizedUtilityTypes())->not()->toContain(SystemReport::class);
});

class DummyUtility extends Utility
{
    public static function displayName(): string
    {
        return 'Dummy';
    }

    public static function id(): string
    {
        return 'dummy';
    }

    public static function contentHtml(): string
    {
        return '';
    }
}
