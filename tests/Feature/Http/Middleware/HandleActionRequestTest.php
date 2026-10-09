<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;

use function Pest\Laravel\post;

beforeEach(function () {
    $this->cpTrigger = Cms::config()->cpTrigger;
});

it('dispatches action-param form posts to the matching action route', function () {
    // Mirrors a native (non-Ajax) full-page CpScreen form submit: the browser
    // POSTs to the current CP page URL — which only has a GET route — with the
    // action carried in a hidden `action` field. The global HandleActionRequest
    // middleware must rewrite this to the real action route rather than letting
    // Laravel 405 on the page URL.
    $response = post("/{$this->cpTrigger}/dashboard", [
        'action' => 'app/api-headers',
    ]);

    $response->assertOk()
        ->assertHeader('content-type', 'application/json');
});
