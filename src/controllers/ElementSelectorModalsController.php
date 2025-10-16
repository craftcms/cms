<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use craft\elements\conditions\StatusConditionRule;
use craft\helpers\Cp;
use Illuminate\Support\Collection;
use yii\web\Response;

/**
 * Class ElementSelectorModalsController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ElementSelectorModalsController extends BaseElementsController
{
    /**
     * Renders and returns the body of an ElementSelectorModal.
     *
     * @return Response
     */
    public function actionBody(): Response
    {
        $this->requireAcceptsJson();

        $elementType = $this->elementType();
        $hasStatuses = $elementType::hasStatuses();

        if ($hasStatuses) {
            $statuses = $elementType::statuses();
            $condition = $this->condition();

            if ($condition) {
                /** @var StatusConditionRule|null $statusRule */
                $statusRule = Collection::make($condition->getConditionRules())
                    ->firstWhere(fn($rule) => $rule instanceof StatusConditionRule);

                if ($statusRule) {
                    $statusValues = $statusRule->getValues();
                    $statuses = Collection::make($statuses)
                        ->filter(function($info, string $status) use ($statusRule, $statusValues) {
                            $inValues = in_array($status, $statusValues);
                            return $statusRule->operator === 'in' ? $inValues : !$inValues;
                        });
                }
            }
        }

        return $this->asJson([
            'html' => Cp::elementIndexHtml($elementType, [
                'class' => 'content',
                'context' => $this->context(),
                'registerJs' => false,
                'showSiteMenu' => $this->request->getParam('showSiteMenu', 'auto'),
                'showStatusMenu' => $hasStatuses,
                'sources' => $this->request->getParam('sources'),
                'statuses' => $statuses ?? null,
            ]),
        ]);
    }
}
