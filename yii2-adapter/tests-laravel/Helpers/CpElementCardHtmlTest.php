<?php

declare(strict_types=1);

use craft\events\DefineElementHtmlEvent;
use craft\helpers\Cp;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use yii\base\Event as YiiEvent;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::findOne());
});

it('applies Craft 5 card HTML handlers to both card renderers', function() {
    YiiEvent::on(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML, function(DefineElementHtmlEvent $event) {
        $event->html = str_replace('class="card-content"', 'class="card-content legacy-content"', $event->html);
    });

    try {
        $html = app(ElementHtml::class)->elementCardHtml(User::findOne());
        $data = app(ElementHtml::class)->elementCardData(User::findOne());
    } finally {
        YiiEvent::off(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML);
    }

    expect($html)->toContain('class="card-content legacy-content"')
        ->and($data['cardContentHtml'])->toContain('class="card-content legacy-content"');
});

it('keeps card data untouched when Craft 5 card HTML handlers leave the HTML alone', function() {
    $user = User::findOne();
    $expected = app(ElementHtml::class)->elementCardData($user, ['id' => 'card-fixed']);

    YiiEvent::on(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML, function(DefineElementHtmlEvent $event) {
    });

    try {
        $data = app(ElementHtml::class)->elementCardData($user, ['id' => 'card-fixed']);
    } finally {
        YiiEvent::off(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML);
    }

    expect($data['cardAttributes'])->toBe($expected['cardAttributes'])
        ->and($data['cardContentHtml'])->toBe($expected['cardContentHtml']);
});
