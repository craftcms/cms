<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\ModalIndexViewModel;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\t;

readonly class ElementSelectorModalController
{
    public function __invoke(ElementIndexRequest $request, ElementIndexHtml $elementIndexHtml, CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $request->validate([
            'showSiteMenu' => ['nullable', 'in:0,1'],
            'siteIds' => ['nullable', 'array', 'min:1'],
            'sources' => ['nullable', function (string $attribute, mixed $value, $fail): void {
                if (! is_array($value) && ! is_string($value)) {
                    $fail(t('The {attribute} field must be a string or array.', ['attribute' => $attribute]));
                }
            }],
            'sources.*' => ['string'],
        ]);

        $elementType = $request->elementType();
        $currentElementIndex->activate();
        $condition = $request->condition();
        $hasStatuses = $elementType::hasStatuses();

        if ($hasStatuses) {
            $statuses = $elementType::statuses();

            if ($condition) {
                /** @var StatusConditionRule|null $statusRule */
                $statusRule = collect($condition->getConditionRules())
                    ->firstWhere(fn ($rule) => $rule instanceof StatusConditionRule);

                if ($statusRule) {
                    $statusValues = $statusRule->getValues();
                    $statuses = collect($statuses)
                        ->filter(function ($info, string $status) use ($statusRule, $statusValues) {
                            $inValues = in_array($status, $statusValues);

                            return $statusRule->operator === 'in' ? $inValues : ! $inValues;
                        });
                }
            }
        }

        $sources = $request->input('sources');

        return new JsonResponse([
            'html' => $elementIndexHtml->html($elementType, [
                'class' => 'content',
                'context' => $request->context(),
                'registerJs' => false,
                'showSiteMenu' => $request->input('showSiteMenu', 'auto'),
                'siteIds' => $request->input('siteIds'),
                'showStatusMenu' => $hasStatuses,
                'sources' => $sources,
                'statuses' => $statuses ?? null,
            ]),
            // The same payload the index screens render from. Served alongside
            // the HTML rather than instead of it: the modal still boots the
            // legacy index off `html`, and will keep doing so until it mounts
            // the Vue index.
            'props' => new ModalIndexViewModel(
                $elementType,
                $request,
                restrictToSources: is_array($sources) ? array_values($sources) : null,
            )->toArray(),
        ]);
    }
}
