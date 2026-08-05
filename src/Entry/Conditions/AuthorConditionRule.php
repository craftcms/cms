<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseElementSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;
use Override;

use function CraftCms\Cms\t;

/**
 * Author condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class AuthorConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
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

    public function getExclusiveQueryParams(): array
    {
        return ['author', 'authorId'];
    }

    /** @param EntryQuery<Entry> $query */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->authorId($this->getElementIds());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue($element->getAuthorId());
    }
}
