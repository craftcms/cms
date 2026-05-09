<?php

declare(strict_types=1);

use CraftCms\Cms\Console\Commands\Install\InstallCommand;

it('warns when craft is already installed', function () {
    $this->artisan('craft:install')
        ->expectsOutputToContain('Craft is already installed!')
        ->assertSuccessful();
});

it('exposes the timezone option in the command signature', function () {
    $definition = app(InstallCommand::class)->getDefinition();

    expect($definition->hasOption('timezone'))->toBeTrue()
        ->and($definition->getOption('timezone')->getDescription())
        ->toContain('default timezone');
});

it('exposes site, account and language options in the command signature', function () {
    $definition = app(InstallCommand::class)->getDefinition();

    expect($definition->hasOption('email'))->toBeTrue()
        ->and($definition->hasOption('username'))->toBeTrue()
        ->and($definition->hasOption('password'))->toBeTrue()
        ->and($definition->hasOption('siteName'))->toBeTrue()
        ->and($definition->hasOption('siteUrl'))->toBeTrue()
        ->and($definition->hasOption('language'))->toBeTrue();
});

it('registers the install command aliases', function () {
    $command = app(InstallCommand::class);

    expect($command->getAliases())->toEqualCanonicalizing(['craft:install:craft', 'craft:install/craft']);
});
