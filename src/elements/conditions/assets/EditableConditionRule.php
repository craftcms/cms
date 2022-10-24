<?php

namespace craft\elements\conditions\assets;

use Craft;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

/**
 * Asset editable condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class EditableConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Editable');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user || $user->admin) {
            $admin = $user?->admin;
            if ((!$admin && $this->value) || ($admin && !$this->value)) {
                $query->id(false);
            }
            return;
        }

        $fullySavableVolumes = [];
        $restrictedVolumes = [];

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            if ($user->can("savePeerAssets:$volume->uid")) {
                $fullySavableVolumes[] = $volume->id;
            } elseif ($user->can("saveAssets:$volume->uid")) {
                $restrictedVolumes[] = $volume->id;
            }
        }

        if (!$fullySavableVolumes && !$restrictedVolumes) {
            if ($this->value) {
                $query->id(false);
            }
            return;
        }

        $condition = [];

        if ($fullySavableVolumes) {
            $condition[] = ['assets.volumeId' => $fullySavableVolumes];
        }

        if ($restrictedVolumes) {
            $condition[] = [
                'assets.volumeId' => $restrictedVolumes,
                'assets.uploaderId' => $user->id,
            ];
        }

        if (count($condition) === 1) {
            $condition = reset($condition);
        } else {
            array_unshift($condition, 'or');
        }

        if (!$this->value) {
            $condition = ['not', $condition];
        }

        $query->andWhere($condition);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        $savable = Craft::$app->getElements()->canSave($element);
        return $savable === $this->value;
    }
}
