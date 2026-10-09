<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Event\MissingTranslationEvent;
use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\Translator;

test('translator dispatches missing translation events through Laravel', function () {
    Event::fake([MissingTranslationEvent::class]);
    app()->forgetInstance(Translator::class);

    $translator = app(Translator::class);
    $translator->addCategorySources(new CategorySource('custom', new class implements MessageReaderInterface
    {
        public function getMessage(string $id, string $category, string $locale, array $parameters = []): ?string
        {
            return null;
        }

        public function getMessages(string $category, string $locale): array
        {
            return [];
        }
    }));

    $translator->translate('Unavailable message', category: 'custom', locale: 'nl');

    Event::assertDispatched(fn (MissingTranslationEvent $event): bool => $event->getCategory() === 'custom'
        && $event->getLanguage() === 'nl'
        && $event->getMessage() === 'Unavailable message');
});
