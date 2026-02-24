<?php

declare(strict_types=1);

test('renders the page', function () {
    $this->loginAndVisitCp('utilities/system-report')
        ->assertSee('Application Info')
        ->assertSee('Plugins')
        ->assertSee('Modules')
        ->assertSee('Aliases')
        ->assertSee('Requirements');
});
