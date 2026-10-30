<?php

declare(strict_types=1);

use craft\fields\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::findOne());
});

it('renders the shared relation field settings template through craft.cp', function() {
    $html = (new Users())->getSettingsHtml();

    expect($html)
        ->toContain('<craft-field')
        ->toContain('<craft-switch')
        ->not->toContain('&lt;craft-');
});
