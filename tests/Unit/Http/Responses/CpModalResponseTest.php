<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Responses\CpModalResponse;
use Twig\Markup;

it('accepts markup for html sections', function (string $method, string $property) {
    $markup = new Markup('<div>HTML</div>', 'UTF-8');
    $response = new CpModalResponse;

    expect($response->$method($markup))->toBe($response)
        ->and($response->$property)->toBe($markup);
})->with([
    ['contentHtml', 'contentHtml'],
    ['errorSummary', 'errorSummary'],
]);
