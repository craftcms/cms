<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\Concerns\HasVisibility;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

/**
 * Settings that only mean something for the index view mode are hidden rather
 * than dropped, so switching away and back keeps whatever was chosen.
 *
 * @see HasVisibility
 */

/** @return array<string, bool> Whether each labelled field is visible. */
function matrixSettingsVisibility(Matrix $field): array
{
    $payload = app(UiResolver::class)->resolve($field->settingsUi(), new UiContext);
    $visible = [];

    $walk = function (array $nodes) use (&$walk, &$visible): void {
        foreach ($nodes as $node) {
            /** @var NodePayload $node */
            $label = $node->props['label'] ?? null;

            if (is_string($label)) {
                $visible[$label] = ! ($node->props['hidden'] ?? false);
            }

            $walk($node->children ?? []);
        }
    };

    $walk($payload->nodes);

    return $visible;
}

beforeEach(fn () => actingAs(User::findOne()));

it('illustrates each view mode, the way a relation field does', function () {
    $payload = app(UiResolver::class)->resolve((new Matrix)->settingsUi(), new UiContext);
    $options = [];

    $walk = function (array $nodes) use (&$walk, &$options): void {
        foreach ($nodes as $node) {
            /** @var NodePayload $node */
            if (($node->control?->path[0] ?? null) === 'viewMode') {
                $options = $node->control->props['options'];
            }

            $walk($node->children ?? []);
        }
    };

    $walk($payload->nodes);

    expect($options)->toHaveCount(4);

    foreach ($options as $option) {
        expect($option['thumbnail']['src'])
            ->toContain("images/view-modes/{$option['value']}.svg");
    }
});

it('hides the index-only settings for other view modes', function () {
    $field = new Matrix;
    $field->viewMode = Matrix::VIEW_MODE_BLOCKS;
    $field->includeTableView = true;

    $visible = matrixSettingsVisibility($field);

    expect($visible['Include Table View'])->toBeFalse()
        ->and($visible['Entries Per Page'])->toBeFalse()
        // Even with the table switched on, there is no table off the index.
        ->and($visible['Default Table Columns'])->toBeFalse();
});

it('shows them for the index view mode', function () {
    $field = new Matrix;
    $field->viewMode = Matrix::VIEW_MODE_INDEX;
    $field->includeTableView = true;

    $visible = matrixSettingsVisibility($field);

    expect($visible['Include Table View'])->toBeTrue()
        ->and($visible['Entries Per Page'])->toBeTrue()
        ->and($visible['Default Table Columns'])->toBeTrue();
});

it('keeps the column picker hidden until the table view is switched on', function () {
    $field = new Matrix;
    $field->viewMode = Matrix::VIEW_MODE_INDEX;
    $field->includeTableView = false;

    $visible = matrixSettingsVisibility($field);

    expect($visible['Include Table View'])->toBeTrue()
        ->and($visible['Default Table Columns'])->toBeFalse();
});
