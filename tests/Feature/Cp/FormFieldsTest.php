<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;

it('throws for an invalid site id in multi-site mode', function () {
    Site::factory()->create();
    Sites::refreshSites();

    expect(fn () => FormFields::fieldHtml('<input>', ['siteId' => -1]))
        ->toThrow(InvalidArgumentException::class, 'Invalid site ID: -1');
});
