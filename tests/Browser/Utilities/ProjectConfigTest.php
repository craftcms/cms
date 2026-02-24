<?php

declare(strict_types=1);

test('renders the page', function () {
    $this->loginAndVisitCp('utilities/project-config')
        ->assertSee('Project Config')
        ->assertSee('Apply YAML Changes')
        ->assertSee('Rebuild the Config')
        ->assertSee('Loaded Project Config Data');
});

test('applies YAML changes', function () {
    $this->loginAndVisitCp('utilities/project-config')
        ->press('Reapply everything');
});

test('rebuilds the config', function () {
    $this->loginAndVisitCp('utilities/project-config')
        ->press('Rebuild');
});
