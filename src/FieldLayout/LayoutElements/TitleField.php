<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Concerns\ImportableFieldLayoutElement;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\Support\Arr;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

class TitleField extends TextField implements ImportableFieldLayoutElementInterface
{
    use ImportableFieldLayoutElement;

    #[Override]
    public bool $mandatory = true;

    #[Override]
    public string $attribute = 'title';

    #[Override]
    public bool $translatable = true;

    #[Override]
    public ?int $maxlength = 255;

    #[Override]
    public bool $required = true;

    #[Override]
    public bool $autofocus = true;

    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        parent::__construct(Arr::except($config, [
            'mandatory',
            'attribute',
            'translatable',
            'maxlength',
            'required',
            'autofocus',
        ]));
    }

    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'attribute',
            'translatable',
            'maxlength',
            'required',
            'autofocus',
        ]);
    }

    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Title');
    }

    #[Override]
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        return true;
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (currentUser()?->isAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }

    #[Override]
    public function canBeMatchCriteria(): bool
    {
        return true;
    }

    #[Override]
    public function canBeCleared(): bool
    {
        return true;
    }
}
