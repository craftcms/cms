<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\base\Chippable;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\models\FieldLayout;

/**
 * FieldUsageBlocker lists the field layouts that use a field, and how many elements have a value for it in each.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class FieldUsageBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * @var array<string, FieldLayout[]> Field layouts fetched from their element types, indexed by element type
     */
    private array $typeLayouts = [];

    /**
     * Constructor
     */
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
        return Craft::t('app', 'The field is used by {num, number} {num, plural, =1{field layout} other{field layouts}}.', [
            'num' => count($this->getItems()),
        ]);
    }

    public function getResolutionLabel(): string
    {
        return Craft::t('app', 'Remove from {num, plural, =1{field layout} other{field layouts}}', [
            'num' => count($this->getItems()),
        ]);
    }

    protected function getResolvedMessage(): string
    {
        return Craft::t('app', 'The field will be removed from the {num, plural, =1{field layout} other{field layouts}} once it’s deleted.', [
            'num' => count($this->getItems()),
        ]);
    }

    protected function defineItems(): array
    {
        $fieldsService = Craft::$app->getFields();
        $items = [];

        foreach ($fieldsService->findFieldUsages($this->field) as $layout) {
            $layoutFields = array_filter(
                $layout->getCustomFields(),
                fn(FieldInterface $field) => $field->id === $this->field->id,
            );
            $count = $fieldsService->countElementsWithValues($layout, $layoutFields);
            /** @var class-string<ElementInterface> $elementType */
            $elementType = $layout->type;

            $items[] = [
                'label' => $this->layoutLabel($layout),
                'count' => $count,
                'description' => self::valueCountDescription($elementType, $count),
            ];
        }

        return $items;
    }

    private function layoutLabel(FieldLayout $layout): string
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $layout->type;

        // Fetch the layout from its element type, so it has a chance to supply its provider
        $this->typeLayouts[$elementType] ??= $elementType::fieldLayouts(null);

        foreach ($this->typeLayouts[$elementType] as $typeLayout) {
            if ($typeLayout->uid === $layout->uid && $typeLayout->provider instanceof Chippable) {
                return $typeLayout->provider->getUiLabel();
            }
        }

        return Craft::t('app', '{type} field layout', [
            'type' => $elementType::displayName(),
        ]);
    }
}
