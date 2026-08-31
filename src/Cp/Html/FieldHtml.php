<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\Colorable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;

use function CraftCms\Cms\t;

#[Singleton]
readonly class FieldHtml
{
    public function __construct(
        private Fields $fields,
        private ContentHtml $contentHtml,
    ) {}

    /**
     * Renders the metadata pane for a saved field (ID + where it's used).
     */
    public function metadataHtml(FieldInterface $field): string
    {
        return $this->contentHtml->metadataHtml([
            t('ID') => $field->id,
            t('Used by') => fn () => $this->usagesHtml($field),
        ]);
    }

    private function usagesHtml(FieldInterface $field): string
    {
        $layouts = $this->fields->findFieldUsages($field);

        if ($layouts->isEmpty()) {
            return Html::tag('i', t('No usages'));
        }

        /** @var FieldLayout[][] $layoutsByType */
        $layoutsByType = $layouts
            ->keyBy('uid')
            ->groupBy(fn (FieldLayout $layout) => $layout->type ?? '__UNKNOWN__')
            ->all();

        /** @var FieldLayout[] $unknownLayouts */
        $unknownLayouts = Arr::pull($layoutsByType, '__UNKNOWN__');
        /** @var FieldLayout[] $layoutsWithProviders */
        $layoutsWithProviders = [];

        // re-fetch as many of these as we can from the element types,
        // so they have a chance to supply the layout providers
        foreach ($layoutsByType as $type => &$typeLayouts) {
            /** @var class-string<ElementInterface> $type */
            /** @phpstan-ignore-next-line */
            foreach ($type::fieldLayouts(null) as $layout) {
                if (isset($typeLayouts[$layout->uid]) && $layout->provider instanceof Chippable) {
                    $layoutsWithProviders[] = $layout;
                    unset($typeLayouts[$layout->uid]);
                }
            }
        }
        unset($typeLayouts);

        $labels = [];
        $items = array_map(function (FieldLayout $layout) use (&$labels) {
            /** @var FieldLayoutProviderInterface&Chippable $provider */
            $provider = $layout->provider;
            $label = $labels[] = $provider->getUiLabel();
            $url = $provider instanceof CpEditable ? $provider->getCpEditUrl() : null;
            $icon = $provider instanceof Iconic ? $provider->getIcon() : null;

            $labelHtml = Html::beginTag('span', [
                'class' => ['flex', 'flex-nowrap', 'gap-md'],
            ]);
            if ($icon) {
                $labelHtml .= Html::tag('div', Icons::svg($icon), [
                    'class' => array_filter([
                        'cp-icon',
                        'small',
                        $provider instanceof Colorable ? $provider->getColor()?->value : null,
                    ]),
                ]);
            }
            $labelHtml .= Html::tag('span', Html::encode($label)).
                Html::endTag('span');

            return $url ? Html::a($labelHtml, $url) : $labelHtml;
        }, $layoutsWithProviders);

        // sort by label
        array_multisort($labels, SORT_ASC, $items);

        foreach ($layoutsByType as $type => $typeLayouts) {
            // any remaining layouts for this type?
            if (! empty($typeLayouts)) {
                /** @var class-string<ElementInterface> $type */
                $items[] = t('{total, number} {type} {total, plural, =1{field layout} other{field layouts}}', [
                    'total' => count($typeLayouts),
                    'type' => $type::lowerDisplayName(),
                ]);
            }
        }

        if (! empty($unknownLayouts)) {
            $items[] = t('{total, number} {type} {total, plural, =1{field layout} other{field layouts}}', [
                'total' => count($unknownLayouts),
                'type' => t('unknown'),
            ]);
        }

        $items = array_map(fn ($item) => Html::li($item)->encode(false), $items);

        return Html::ul()->items(...$items)->render();
    }
}
