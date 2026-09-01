<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use CraftCms\Cms\Utility\Utilities\DbBackup;
use CraftCms\Cms\Utility\Utilities\SystemMessages;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\Utility\UtilityTypes;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->utilities = app(Utilities::class);
});

it('contains system messages when craft is pro', function () {
    Edition::set(Edition::Solo);

    expect($this->utilities->getAllUtilityTypes())->not()->toContain(SystemMessages::class);

    Edition::set(Edition::Pro);

    expect($this->utilities->getAllUtilityTypes())->toContain(SystemMessages::class);
});

it('does not contain the asset indexes utility when there are no volumes', function () {
    expect($this->utilities->getAllUtilityTypes())->not()->toContain(AssetIndexes::class);
});

it('filters unavailable registered utilities', function () {
    app(UtilityTypes::class)->register(DummyUtility::class, UnselectableUtility::class);

    Cms::config()->backupCommand = false;
    Cms::config()->disabledUtilities[] = DummyUtility::id();

    expect($this->utilities->getAllUtilityTypes())
        ->not()->toContain(DbBackup::class)
        ->not()->toContain(DummyUtility::class)
        ->not()->toContain(UnselectableUtility::class);
});

it('can get authorized utility types', function () {
    expect($this->utilities->getAuthorizedUtilityTypes())->toBeEmpty();

    actingAs(User::find()->one());

    expect($this->utilities->getAuthorizedUtilityTypes())->not()->toBeEmpty();
});

class DummyUtility extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return 'Dummy';
    }

    #[Override]
    public static function id(): string
    {
        return 'dummy';
    }

    #[Override]
    public static function contentHtml(): string
    {
        return '';
    }
}

class UnselectableUtility extends DummyUtility
{
    #[Override]
    public static function id(): string
    {
        return 'unselectable';
    }

    #[Override]
    public static function isSelectable(): bool
    {
        return false;
    }
}
