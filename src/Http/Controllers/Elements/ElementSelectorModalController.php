<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use Illuminate\Http\JsonResponse;

readonly class ElementSelectorModalController extends BaseElementsController
{
    public function __invoke(ElementIndexHtml $elementIndexHtml): JsonResponse
    {
        $this->request->validate([
            'showSiteMenu' => ['nullable', 'in:0,1'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string'],
        ]);

        $elementType = $this->elementType();
        $condition = $this->condition();
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
                'context' => $this->context(),
                'registerJs' => false,
                'showSiteMenu' => $this->request->input('showSiteMenu', 'auto'),
                'showStatusMenu' => $hasStatuses,
                'sources' => $this->request->input('sources'),
                'statuses' => $statuses ?? null,
            ]),
        ]);
    }
}
