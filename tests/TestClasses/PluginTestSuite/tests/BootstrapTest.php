<?php

declare(strict_types=1);

use Acme\BootstrapPlugin\Plugin;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('installs Craft and the plugin with its install migration', function () {
    expect(Cms::isInstalled())->toBeTrue()
        ->and(app(Plugins::class)->getPlugin('bootstrap-plugin'))->toBeInstanceOf(Plugin::class)
        ->and(Schema::hasTable('bootstrap_plugin_records'))->toBeTrue()
        ->and(DB::table(Table::MIGRATIONS)->where('track', 'plugin:bootstrap-plugin')->where('migration', 'Install')->exists())->toBeTrue()
        ->and(app(Sites::class)->getPrimarySite()->name)->toBe('Craft test site');

    $this->artisan('craft:plugin:list')->expectsOutputToContain('1.0.0')->assertSuccessful();

    $this->get('/bootstrap-plugin')->assertOk()->assertJson(['plugin' => 'bootstrap-plugin']);

    $this->actingAs(CraftCms\Cms\User\Models\User::first(), 'craft')
        ->get(route('bootstrap-plugin.index'))
        ->assertOk()->assertJson(['plugin' => 'bootstrap-plugin']);
});

it('saves and queries elements', function () {
    $user = User::find()->one();
    $user->email = 'plugin-tester@example.com';
    $user->fullName = 'Plugin Tester';

    expect(app(Elements::class)->saveElement($user))->toBeTrue()
        ->and(User::find()->id($user->id)->one()?->email)->toBe('plugin-tester@example.com');
});
