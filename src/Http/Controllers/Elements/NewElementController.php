<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

/**
 * Creates an unpublished draft of the element type named by the route's `elementType` default, and opens its editor.
 *
 * ```php
 * Route::get('customers/new', NewElementController::class)
 *     ->defaults('elementType', Customer::class);
 * ```
 *
 * @since 6.0.0
 */
readonly class NewElementController
{
    public function __invoke(Request $request, Drafts $drafts): RedirectResponse
    {
        $elementType = $request->route('elementType');

        abort_unless(is_string($elementType) && is_subclass_of($elementType, ElementInterface::class), 404);

        $element = new $elementType;

        Gate::authorize('save', $element);

        $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

        abort_unless(
            $drafts->saveElementAsDraft($element, $request->craftUser()?->getCraftUserId(), markAsSaved: false),
            400,
            mb_ucfirst(t('Couldn’t create {type}.', ['type' => $elementType::lowerDisplayName()])),
        );

        return redirect(Url::urlWithParams((string) $element->getCpEditUrl(), ['fresh' => 1]));
    }
}
