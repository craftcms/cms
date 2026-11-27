<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;

use function CraftCms\Cms\t;

/**
 * Lists the field layouts that use a field, and how many elements have a value for it in each.
 *
 * @since 6.0.0
 */
class FieldUsageBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * Field layouts fetched from their element types, indexed by element type.
     *
     * @var array<class-string<ElementInterface>, FieldLayout[]>
     */
    private array $typeLayouts = [];

    /** @param array<string, mixed> $config */
    public function __construct(
        protected readonly FieldInterface $field,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getKey(): string
    {
        return 'usages';
    }

    public function getSummary(): string
    {
        return t('The field is included in {num, number} {num, plural, =1{field layout} other{field layouts}}.', [
            'num' => count($this->getItems()),
        ]);
    }

    public function getResolutionLabel(): string
    {
        return t('Remove from {num, plural, =1{field layout} other{field layouts}}', [
            'num' => count($this->getItems()),
        ]);
    }

    protected function getResolvedMessage(): string
    {
        return t('The field will be removed from the {num, plural, =1{field layout} other{field layouts}} once it’s deleted.', [
            'num' => count($this->getItems()),
        ]);
    }

    protected function defineItems(): array
    {
        return Fields::findFieldUsages($this->field)
            ->map(function (FieldLayout $layout) {
                $layoutFields = array_filter(
                    $layout->getCustomFields(),
                    fn (FieldInterface $field) => $field->id === $this->field->id,
                );
                $count = Fields::countElementsWithValues($layout, $layoutFields);
                /** @var class-string<ElementInterface> $elementType */
                $elementType = $layout->type;

                return [
                    'label' => $this->layoutLabel($layout),
                    'count' => $count,
                    'description' => self::valueCountDescription($elementType, $count),
                ];
            })
            ->values()
            ->all();
    }

    private function layoutLabel(FieldLayout $layout): string
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $layout->type;

        // Element types set their layouts’ providers, so look the layout up there to name it after its provider
        $this->typeLayouts[$elementType] ??= $elementType::fieldLayouts(null);

        foreach ($this->typeLayouts[$elementType] as $typeLayout) {
            if ($typeLayout->uid === $layout->uid && $typeLayout->provider instanceof Chippable) {
                return $typeLayout->provider->getUiLabel();
            }
        }

        return t('{type} field layout', [
            'type' => $elementType::displayName(),
        ]);
    }
}
