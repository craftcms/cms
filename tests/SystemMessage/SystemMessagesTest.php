<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\SystemMessage\Events\RegisterSystemMessages;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;

use function CraftCms\Cms\t;

beforeEach(function () {
    $this->systemMessages = app(SystemMessages::class);
});

it('retrieves all the default system messages', function () {
    $messages = $this->systemMessages->getAllDefaultMessages();

    expect($messages->has('account_activation'))->toBeTrue();
    expect($this->systemMessages->getDefaultMessage('account_activation'))->not()->toBeNull();
    expect($messages->has('verify_new_email'))->toBeTrue();
    expect($this->systemMessages->getDefaultMessage('verify_new_email'))->not()->toBeNull();
    expect($messages->has('forgot_password'))->toBeTrue();
    expect($this->systemMessages->getDefaultMessage('forgot_password'))->not()->toBeNull();
    expect($messages->has('test_email'))->toBeTrue();
    expect($this->systemMessages->getDefaultMessage('test_email'))->not()->toBeNull();
});

it('can add additional messages through an event', function () {
    \Illuminate\Support\Facades\Event::listen(RegisterSystemMessages::class, function (RegisterSystemMessages $event) {
        $event->messages->push(new SystemMessage([
            'key' => 'foo',
            'heading' => 'A test system message',
            'subject' => 'A test system message',
            'body' => 'A test system message',
        ]));
    });

    expect($this->systemMessages->getAllDefaultMessages()->has('foo'))->toBeTrue();
    expect($this->systemMessages->getDefaultMessage('foo'))->not()->toBeNull();
});

it('can get messages including overrides', function () {
    $edition = Edition::get();

    Edition::set(Edition::Pro);

    expect($this->systemMessages->getAllMessages()->has('account_activation'))->toBeTrue();

    expect($this->systemMessages->getAllMessages()['account_activation']->subject)->toBe(t('account_activation_subject'));
    expect($this->systemMessages->getMessage('account_activation')->subject)->toBe(t('account_activation_subject'));

    $this->systemMessages->saveMessage(new SystemMessage([
        'key' => 'account_activation',
        'language' => 'en-US',
        'subject' => 'Overridden subject',
        'body' => '',
    ]));

    expect($this->systemMessages->getAllMessages()['account_activation']->subject)->toBe('Overridden subject');
    expect($this->systemMessages->getMessage('account_activation')->subject)->toBe('Overridden subject');

    Edition::set($edition);
});
