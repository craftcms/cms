<?php

declare(strict_types=1);

test('renders the page', function () {
    $this->loginAndVisitCp('utilities/updates')
        ->assertSee('Updates');
});
