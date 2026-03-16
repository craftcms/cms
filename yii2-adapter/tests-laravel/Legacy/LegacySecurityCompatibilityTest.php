<?php

declare(strict_types=1);


it('resolves the legacy security component from yii config', function() {
    expect(Craft::$app->getSecurity())
        ->toBeInstanceOf(craft\services\Security::class);
});

it('honors configured sensitive keywords on the legacy security service', function() {
    $security = new craft\services\Security([
        'sensitiveKeywords' => ['credential'],
    ]);

    expect($security->redactIfSensitive('apiCredential', 'secret'))
        ->toBe(str_repeat('•', 6));
});
