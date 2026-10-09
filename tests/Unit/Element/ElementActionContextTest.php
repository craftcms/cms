<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Enums\ElementActionContext;

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
