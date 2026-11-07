<?php

declare(strict_types=1);

use craft\events\RedirectEvent;
use craft\web\ErrorHandler;
use craft\web\RedirectRule;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use yii\base\Event as YiiEvent;

uses(DatabaseTestCase::class);

it('triggers legacy before redirect handlers for redirect rules', function(): void {
    config(['craft.redirects' => ['old-page' => 'new-page']]);
    $receivedRule = null;

    $handler = function(RedirectEvent $event) use (&$receivedRule) {
        $receivedRule = $event->rule;
    };

    YiiEvent::on(ErrorHandler::class, ErrorHandler::EVENT_BEFORE_REDIRECT, $handler);

    try {
        $response = $this->get('/old-page');
    } finally {
        YiiEvent::off(ErrorHandler::class, ErrorHandler::EVENT_BEFORE_REDIRECT, $handler);
    }

    $response->assertRedirect();
    expect($receivedRule)
        ->toBeInstanceOf(RedirectRule::class)
        ->and($receivedRule->from)->toBe('old-page')
        ->and($receivedRule->to)->toBe('new-page');
});
