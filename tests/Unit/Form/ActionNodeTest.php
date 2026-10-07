<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Checkbox;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Action;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function actionsUi(): Ui
{
    return Ui::make([
        Field::make('Label', Text::make('label'))
            ->actions(Action::make(Checkbox::make('labelHidden')->label('Hide'))),
    ]);
}

it('resolves action children with their own control paths', function () {
    $payload = app(UiResolver::class)->resolve(actionsUi(), new UiContext);
    $field = $payload->nodes[0];

    expect($field->props['hasActions'])->toBeTrue()
        ->and($field->control->path)->toBe(['label'])
        ->and($field->children)->toHaveCount(1)
        ->and($field->children[0]->component)->toBe('craft:action')
        ->and($field->children[0]->control->path)->toBe(['labelHidden'])
        ->and($field->children[0]->control->component)->toBe('craft:checkbox')
        ->and($field->children[0]->control->props['label'])->toBe('Hide');
});

it('omits hasActions when a field has no actions', function () {
    $form = Ui::make([Field::make('Label', Text::make('label'))]);
    $payload = app(UiResolver::class)->resolve($form, new UiContext);

    expect($payload->nodes[0]->props)->not->toHaveKey('hasActions')
        ->and($payload->nodes[0]->children)->toBeNull();
});

it('binds values and errors to the action control, not the field control', function () {
    $payload = app(UiResolver::class)->resolve(actionsUi(), new UiContext(
        values: ['label' => 'Heading', 'labelHidden' => true],
        errors: ['labelHidden' => 'Nope.'],
    ));
    $field = $payload->nodes[0];

    expect($payload->values['labelHidden'])->toBeTrue()
        ->and($payload->errors)->toHaveCount(1)
        ->and($payload->errors[0]['path'])->toBe(['labelHidden'])
        ->and($payload->globalErrors)->toBe([])
        ->and($field->children[0]->control->path)->toBe(['labelHidden']);
});

it('renders actions into the field’s actions slot', function () {
    $payload = app(UiResolver::class)->resolve(actionsUi(), new UiContext(
        values: ['labelHidden' => true],
    ));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $action = $crawler->filter('craft-field [slot="actions"]');

    expect($action)->toHaveCount(1)
        ->and($action->filter('input[type="checkbox"][name="labelHidden"]'))->toHaveCount(1);
});
