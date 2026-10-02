<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Yii2Adapter\Form\Concerns\LegacyTextConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\LinkFieldConditionRule instead. */
class LinkFieldConditionRule extends \CraftCms\Cms\Field\Conditions\LinkFieldConditionRule
{
    use LegacyTextConditionRule {
        inputHtml as private baseInputHtml;
    }

    private const string OPERATOR_TYPE = 'type';

    protected function inputHtml(): string
    {
        if ($this->operator !== self::OPERATOR_TYPE) {
            return $this->baseInputHtml();
        }

        /** @var Link $field */
        $field = $this->field();
        $linkTypeOptions = array_map(
            fn(BaseLinkType $linkType) => ['value' => $linkType::id(), 'label' => $linkType::displayName()],
            $field->getLinkTypes(),
        );

        return FormFields::selectHtml([
            'name' => 'linkType',
            'options' => $linkTypeOptions,
            'value' => $this->linkType,
        ]);
    }
}
