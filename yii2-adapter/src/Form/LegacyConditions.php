<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form;

use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Support\Json;

readonly class LegacyConditions extends Conditions
{
    public function createConditionRule(array|string $config): ConditionRuleInterface
    {
        if (is_string($config)) {
            $config = ['class' => $config];
        }

        if (isset($config['class'])) {
            $config['class'] = LegacyConditionClasses::CORE_CLASSES[$config['class']] ?? $config['class'];
        }

        if (isset($config['type'])) {
            $type = Json::decodeIfJson($config['type']);
            if (is_array($type)) {
                $type['class'] = LegacyConditionClasses::CORE_CLASSES[$type['class']] ?? $type['class'];
                $config['type'] = Json::encode($type);
            } else {
                $config['type'] = LegacyConditionClasses::CORE_CLASSES[$type] ?? $type;
            }
        }

        return parent::createConditionRule($config);
    }
}
