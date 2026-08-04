<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Events\ElementCardHtmlResolving;
use CraftCms\Cms\Cp\Events\ElementChipHtmlResolving;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    $this->elementHtml = app(ElementHtml::class);
});

describe('elementChipHtml', function () {
    it('renders field and index variants with expected controls', function () {
        $user = User::findOne(1);

        expect($user)->toBeInstanceOf(User::class);

        $indexHtml = $this->elementHtml->elementChipHtml($user);
        $fieldHtml = $this->elementHtml->elementChipHtml($user, [
            'context' => 'field',
            'inputName' => 'myFieldName[]',
        ]);

        expect($fieldHtml)->toContain('removable')
            ->and($fieldHtml)->toContain('name="myFieldName[]"')
            ->and($indexHtml)->toContain('<craft-indicator')
            ->and($this->elementHtml->elementChipHtml($user, ['showStatus' => false]))->not->toContain('<craft-indicator')
            ->and($indexHtml)->toContain('thumb')
            ->and($this->elementHtml->elementChipHtml($user, ['showThumb' => false]))->not->toContain('thumb');

        $labelPattern = '/<craft-truncate id="[^"]+" class="label">/';
        expect((bool) preg_match($labelPattern, $indexHtml))->toBeTrue()
            ->and((bool) preg_match($labelPattern, (string) $this->elementHtml->elementChipHtml($user, ['showLabel' => false])))->toBeFalse();
    });

    it('adds error marker in field context and trashed data when trashed', function () {
        $user = User::findOne(1);

        expect($user)->toBeInstanceOf(User::class);

        $indexHtml = $this->elementHtml->elementChipHtml($user);
        expect($indexHtml)->not->toContain('error')
            ->and($indexHtml)->not->toContain('data-trashed');

        $user->errors()->add('foo', 'bad error');
        $fieldHtmlWithErrors = $this->elementHtml->elementChipHtml($user, ['context' => 'field']);
        $user->errors()->forget('foo');

        $user->trashed = true;
        $trashedHtml = $this->elementHtml->elementChipHtml($user);
        $user->trashed = false;

        expect($fieldHtmlWithErrors)->toContain('error')
            ->and($trashedHtml)->toContain('data-trashed');
    });

    it('dispatches and allows overriding ElementChipHtmlResolving', function () {
        $user = User::findOne(1);
        expect($user)->toBeInstanceOf(User::class);

        Event::listen(function (ElementChipHtmlResolving $event) {
            $event->html = '<div>overridden-chip</div>';
        });

        $html = $this->elementHtml->elementChipHtml($user);

        expect($html)->toBe('<div>overridden-chip</div>');
    });

    it('links custom element labels when the element authorizes viewing', function () {
        $element = new ElementHtmlTestElement;
        $element->id = 123;
        $element->title = 'Payment #123';

        $html = $this->elementHtml->elementChipHtml($element, [
            'hyperlink' => true,
        ]);

        expect($html)->toContain('<a class="label-link" href="')
            ->and($html)->toContain('/custom-elements/123');
    });
});

describe('elementCardHtml', function () {
    it('renders hidden input in field context', function () {
        $user = User::findOne(1);
        expect($user)->toBeInstanceOf(User::class);

        $html = $this->elementHtml->elementCardHtml($user, [
            'context' => 'field',
            'inputName' => 'myFieldName[]',
        ]);

        expect($html)->toContain('class="card')
            ->and($html)->toContain('name="myFieldName[]"');
    });

    it('dispatches and allows overriding ElementCardHtmlResolving', function () {
        $user = User::findOne(1);
        expect($user)->toBeInstanceOf(User::class);

        Event::listen(function (ElementCardHtmlResolving $event) {
            $event->html = '<div>overridden-card</div>';
        });

        $html = $this->elementHtml->elementCardHtml($user);

        expect($html)->toBe('<div>overridden-card</div>');
    });
});

class ElementHtmlTestElement extends Element
{
    #[Override]
    public function canView(User $user): bool
    {
        return true;
    }

    protected function cpEditUrl(): ?string
    {
        return "custom-elements/$this->id";
    }
}
