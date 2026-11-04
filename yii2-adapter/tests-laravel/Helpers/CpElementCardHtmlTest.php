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

it('splits a Craft 5 card HTML override back into parts for card data', function() {
    $user = User::findOne();
    $config = [
        'id' => 'card-fixed',
        'context' => 'field',
        'inputName' => 'myFieldName[]',
        'attributes' => ['class' => ['override-card']],
    ];
    $expected = app(ElementHtml::class)->elementCardData($user, $config);

    YiiEvent::on(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML, function(DefineElementHtmlEvent $event) {
        $html = str_replace('class="card-content"', 'class="card-content override-content"', $event->html);
        $event->html = str_replace('</craft-card>', '<input type="hidden" name="override"></craft-card>', $html);
    });

    try {
        $data = app(ElementHtml::class)->elementCardData($user, $config);
    } finally {
        YiiEvent::off(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML);
    }

    expect($data['cardAttributes']['class'])->toContain('override-card')
        ->and($data['cardHeaderHtml'])->toBe($expected['cardHeaderHtml'])
        ->and($data['cardThumbHtml'])->toContain('<svg')
        ->and($data['cardContentHtml'])->toStartWith('<div class="card-main"><div class="card-content override-content">')
        ->and($data['cardFooterHtml'])->toContain('name="myFieldName[]"')
        ->and($data['cardFooterHtml'])->toContain('name="override"');
});

it('uses a Craft 5 card HTML override that isn’t a card as the card’s content, keeping its footer', function() {
    YiiEvent::on(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML, function(DefineElementHtmlEvent $event) {
        $event->html = '<div>overridden-card</div>';
    });

    try {
        $data = app(ElementHtml::class)->elementCardData(User::findOne(), [
            'context' => 'field',
            'inputName' => 'myFieldName[]',
            'attributes' => ['class' => ['kept-card']],
        ]);
    } finally {
        YiiEvent::off(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML);
    }

    expect($data['cardAttributes']['class'])->toContain('kept-card')
        ->and($data['cardHeaderHtml'])->toBe('')
        ->and($data['cardActionsHtml'])->toBe('')
        ->and($data['cardContentHtml'])->toBe('<div class="card-main"><div>overridden-card</div></div>')
        ->and($data['cardFooterHtml'])->toContain('name="myFieldName[]"');
});

it('keeps one field input when a Craft 5 handler wraps the card', function() {
    YiiEvent::on(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML, function(DefineElementHtmlEvent $event) {
        $event->html = "<div class=\"wrapper\">$event->html</div>";
    });

    try {
        $html = app(ElementHtml::class)->elementCardHtml(User::findOne(), [
            'context' => 'field',
            'inputName' => 'myFieldName[]',
        ]);
    } finally {
        YiiEvent::off(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML);
    }

    expect(substr_count($html, 'name="myFieldName[]"'))->toBe(1)
        ->and($html)->toContain('class="wrapper"');
});

it('keeps markup a Craft 5 handler adds next to the card', function() {
    YiiEvent::on(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML, function(DefineElementHtmlEvent $event) {
        $event->html .= '<input type="hidden" name="extra">';
    });

    try {
        $html = app(ElementHtml::class)->elementCardHtml(User::findOne());
    } finally {
        YiiEvent::off(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CARD_HTML);
    }

    expect($html)->toContain('name="extra"');
});
