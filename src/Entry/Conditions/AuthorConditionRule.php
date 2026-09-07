<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseElementSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

/**
 * Author condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class AuthorConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Author');
    }

    protected function elementType(): string
    {
        return User::class;
    }

    /** @return array{authors: true} */
    protected function criteria(): ?array
    {
        return [
            'authors' => true,
        ];
    }

    #[Override]
    protected function allowMultiple(): bool
    {
        return true;
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        EntryQuery::applyAuthorId($query, $this->getElementIds());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue($element->getAuthorId());
    }
}
