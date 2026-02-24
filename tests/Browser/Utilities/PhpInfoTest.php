<?php

declare(strict_types=1);

test('renders the page', function () {
    $this->loginAndVisitCp('utilities/php-info')
        ->assertSee('PHP Info');
});
