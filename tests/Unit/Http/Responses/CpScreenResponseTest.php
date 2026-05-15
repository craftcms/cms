<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Twig\Markup;

it('accepts markup for html sections', function (string $method, string $property) {
    $markup = new Markup('<div>HTML</div>', 'UTF-8');
    $response = new CpScreenResponse;

    expect($response->$method($markup))->toBe($response)
        ->and($response->$property)->toBe($markup);
})->with([
    ['toolbarHtml', 'toolbarHtml'],
    ['additionalButtonsHtml', 'additionalButtonsHtml'],
    ['contentHtml', 'contentHtml'],
    ['metaSidebarHtml', 'metaSidebarHtml'],
    ['pageSidebarHtml', 'pageSidebarHtml'],
    ['noticeHtml', 'noticeHtml'],
    ['errorSummary', 'errorSummary'],
]);
