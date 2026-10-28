<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Events\ElementCardHtmlResolving;
use CraftCms\Cms\Cp\Events\ElementChipHtmlResolving;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Tests\TestClasses\Asset\ControlPanelAssetTransformDriver;
use CraftCms\Cms\Tests\TestClasses\Field\ModeThumbnailField;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    $this->elementHtml = app(ElementHtml::class);
});

describe('elementChipHtml', function () {
    it('fits thumbnails in both chip sizes', function (string $size, ?string $appearance, int $pixels) {
        $entry = Entry::factory()->withField('thumbnail', ModeThumbnailField::class, value: 'fit')
            ->createElementWithFields()->element;
        $layout = $entry->getFieldLayout();
        $layout->thumbFieldKey = 'layoutElement:'.$layout->getCustomFieldElements()[0]->uid;

        expect($this->elementHtml->elementChipHtml($entry, ['size' => $size, 'appearance' => $appearance]))
            ->toContainTag('craft-thumbnail', ['mode' => 'fit', 'sizes' => "calc({$pixels}rem/16)"]);
    })->with([
        'small' => ['small', null, 26],
        'small plain' => ['small', 'plain', 30],
        'large' => ['large', null, 120],
    ]);

    it('leaves out the thumbnail when the element has none', function () {
        $entry = Entry::factory()->createElement();

        $html = $this->elementHtml->elementChipHtml($entry);

        expect($html)->not->toContainTag('craft-chip', ['show-thumb' => true])
            ->and($html)->not->toContain('slot="thumbnail"')
            ->and($html)->toContain('"showThumb":true');
    });

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

    it('truncates a draft’s title on its own, so its draft label can wrap below it', function () {
        $canonical = Entry::factory()->createElement(['title' => 'Original']);
        $draft = app(Drafts::class)->createDraft(canonical: $canonical, creatorId: 1, name: 'Spring programme');

        expect($this->elementHtml->elementChipHtml($draft))
            ->toMatch('/<span id="[^"]+" class="label"><span class="label-link"><craft-truncate>Original<\/craft-truncate><span class="context-label">Spring programme<\/span><\/span><\/span>/')
            ->and($this->elementHtml->elementChipHtml($canonical))
            ->toMatch('/<craft-truncate id="[^"]+" class="label"><span class="label-link">Original<\/span><\/craft-truncate>/');
    });

    it('lets a selectable chip render its own labelled checkbox', function () {
        $user = User::findOne();

        $html = $this->elementHtml->elementChipHtml($user, ['selectable' => true]);

        expect($html)
            ->toContainTag('craft-chip', [
                'selectable' => true,
                'select-label' => "Select {$user->getUiLabel()}",
            ])
            ->not->toContain('class="checkbox"');
    });
});

describe('elementCardHtml', function () {
    it('uses fit consistently for inline and separate thumbnails and presence', function (string $supportedMode) {
        $entry = Entry::factory()->withField('thumbnail', ModeThumbnailField::class, value: $supportedMode)
            ->createElementWithFields()->element;
        $layout = $entry->getFieldLayout();
        $layout->thumbFieldKey = 'layoutElement:'.$layout->getCustomFieldElements()[0]->uid;
        $attributes = $this->elementHtml->elementCardAttributes($entry);
        $inline = $this->elementHtml->elementCardHtml($entry);
        $separate = $this->elementHtml->elementCardThumbHtml($entry);

        expect($this->elementHtml->elementCardContentHtml($entry, ['withThumb' => false]))
            ->not->toContainTag('craft-thumbnail');

        if ($supportedMode === 'fit') {
            expect($attributes['class'])->toContain('thumb-end');
            expect($inline)->toContainTag('craft-thumbnail', ['mode' => 'fit', 'sizes' => 'calc(120rem/16)']);
            expect($separate)->toContainTag('craft-thumbnail', ['mode' => 'fit', 'sizes' => 'calc(120rem/16)']);
        } else {
            expect($attributes['class'])->not->toContain('thumb-end');
            expect($inline)->not->toContainTag('craft-thumbnail');
            expect($separate)->toBe('');
        }
    })->with(['fit provider' => 'fit', 'crop-only provider' => 'crop']);

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

    it('covers an asset’s card only with an opaque image that can fill it', function (string $filename, int $width, int $height, bool $covered, string $color = '#3a6ea5') {
        new ControlPanelAssetTransformDriver()->register();
        Cms::config()->defaultAssetTransformer('test');
        $asset = Asset::factory()->createElement(['filename' => $filename, 'width' => $width, 'height' => $height]);
        $luminance = [[[0.1, 0.6], null], [[0.02, 0.3], [0.05, 0.4]]];
        $asset->analysis = new ImageAnalysis(colors: new ImageColors(grid: [[$color]], luminance: $luminance));

        $attributes = $this->elementHtml->elementCardAttributes($asset);

        expect($attributes['cover-src'] ?? null)->toBe($covered ? '/transforms/800x600.webp' : null)
            ->and($attributes['luminance'] ?? null)->toBe($covered ? $luminance : null);
    })->with([
        'smallest' => ['photo.jpg', 480, 360, true],
        'too narrow' => ['photo.jpg', 479, 360, false],
        'too short' => ['photo.jpg', 480, 359, false],
        'widest' => ['photo.jpg', 1200, 600, true],
        'too wide' => ['photo.jpg', 1201, 600, false],
        'tallest' => ['photo.jpg', 600, 800, true],
        'too tall' => ['photo.jpg', 599, 800, false],
        'opaque png' => ['logo.png', 800, 600, true],
        'transparent' => ['logo.png', 800, 600, false, '#3a6ea580'],
    ]);

    it('covers an element’s card with the image its thumbnail field shows, with the cover thumb style', function (int $width, string $style, bool $covered) {
        new ControlPanelAssetTransformDriver()->register();
        Cms::config()->defaultAssetTransformer('test');
        $luminance = [[[0.1, 0.6]], [[0.02, 0.3]]];
        $asset = Asset::factory()->createElement([
            'width' => $width,
            'height' => 600,
            'analysis' => ['colors' => ['grid' => [['#3a6ea5']], 'luminance' => $luminance]],
        ]);
        $entry = Entry::factory()->withField('images', Assets::class, value: [$asset->id])
            ->createElementWithFields()->element;
        $layout = $entry->getFieldLayout();
        $layout->thumbFieldKey = 'layoutElement:'.$layout->getCustomFieldElements()[0]->uid;
        $layout->setCardThumbStyle($style);

        $attributes = $this->elementHtml->elementCardAttributes($entry);

        expect($attributes['cover-src'] ?? null)->toBe($covered ? '/transforms/800x600.webp' : null)
            ->and($attributes['luminance'] ?? null)->toBe($covered ? $luminance : null);
    })->with([
        'fills the card' => [800, 'cover', true],
        'too small' => [400, 'cover', false],
        'normal thumb style' => [800, 'normal', false],
    ]);

    it('keeps an asset’s normal card when its volume uses the normal thumb style', function () {
        new ControlPanelAssetTransformDriver()->register();
        Cms::config()->defaultAssetTransformer('test');
        $asset = Asset::factory()->createElement(['filename' => 'photo.jpg', 'width' => 800, 'height' => 600]);
        $asset->analysis = new ImageAnalysis(colors: new ImageColors(grid: [['#3a6ea5']], luminance: [[[0.1, 0.6]]]));
        $asset->getFieldLayout()->setCardThumbStyle('normal');

        expect($this->elementHtml->elementCardAttributes($asset)['cover-src'] ?? null)->toBeNull();
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
