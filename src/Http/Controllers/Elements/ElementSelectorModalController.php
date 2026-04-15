<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Http\Requests\ElementRequest;
use Illuminate\Http\JsonResponse;

readonly class ElementSelectorModalController
{
    public function __invoke(ElementRequest $request, ElementIndexHtml $elementIndexHtml, CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $request->validate([
            'showSiteMenu' => ['nullable', 'in:0,1'],
            'sources' => ['nullable', 'array'],
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

        return new JsonResponse([
            'html' => $elementIndexHtml->html($elementType, [
                'class' => 'content',
                'context' => $request->context(),
                'registerJs' => false,
                'showSiteMenu' => $request->input('showSiteMenu', 'auto'),
                'showStatusMenu' => $hasStatuses,
                'sources' => $request->input('sources'),
                'statuses' => $statuses ?? null,
            ]),
        ]);
    }
}
