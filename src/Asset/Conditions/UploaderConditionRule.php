<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseElementSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class UploaderConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Uploaded By');
    }

    protected function elementType(): string
    {
        return User::class;
    }

    /** @return array{assetUploaders: true} */
    protected function criteria(): array
    {
        return [
            'assetUploaders' => true,
        ];
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        AssetQuery::applyUploaderId($query, $this->getElementId());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->uploaderId);
    }
}
