<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Entry\Elements\Entry;

// Everywhere but the element's own edit screen draws it as a chip or card.
it('treats only the editor as the element’s own screen', function () {
    expect(ElementActionContext::Editor->isEditor())->toBeTrue()
        ->and(ElementActionContext::Index->isEditor())->toBeFalse()
        ->and(ElementActionContext::Field->isEditor())->toBeFalse()
        ->and(ElementActionContext::Modal->isEditor())->toBeFalse();
});

// The values match the `context` strings the rest of the CP passes around, so
// the two can be converted rather than kept in step by hand.
it('shares its values with the CP’s context strings', function () {
    expect(ElementActionContext::from('field'))->toBe(ElementActionContext::Field)
        ->and(ElementActionContext::Index->value)->toBe('index');
});

// The flag is the extension point Craft 5 documents on `Actionable`: every
// non-destructive item shows by default, and any item can opt in or out.
it('honours showInChips over the destructive default', function (array $item, bool $expected) {
    $method = new ReflectionMethod(Entry::class, 'showsInChips');

    expect($method->invoke(null, $item))->toBe($expected);
})->with([
    'plain item shows' => [['label' => 'View'], true],
    'destructive item hides' => [['label' => 'Delete', 'destructive' => true], false],
    'opted out hides' => [['label' => 'Replace file', 'showInChips' => false], false],
    'destructive but opted in shows' => [
        ['label' => 'Odd one', 'destructive' => true, 'showInChips' => true],
        true,
    ],
]);
