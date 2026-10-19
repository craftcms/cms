<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Controls\Link as LinkControl;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Normalizes, validates, and localizes link values for a set of link settings.
 *
 * @since 6.0.0
 */
class LinkResolver
{
    /** @var array<string, BaseLinkType> */
    private array $linkTypes;

    /** @var list<array<string, mixed>> */
    private array $pickerConfigs;

    /**
     * @param  list<string>  $types
     * @param  array<string, array<string, mixed>>  $typeSettings
     * @param  array<'urlSuffix'|'target'|'title'|'class'|'id'|'rel'|'ariaLabel'|'download'>  $advancedFields
     */
    public function __construct(
        public readonly array $types = ['entry', 'url'],
        public readonly array $typeSettings = [],
        public readonly bool $showLabelField = false,
        public readonly array $advancedFields = [],
        public readonly int $maxLength = 255,
    ) {}

    /**
     * Returns the allowed link types.
     *
     * @return array<string, BaseLinkType>
     */
    public function linkTypes(): array
    {
        if (! isset($this->linkTypes)) {
            $this->linkTypes = [];
            $types = Link::types();

            foreach ($this->types as $typeId) {
                if (isset($types[$typeId])) {
                    $this->linkTypes[$typeId] = ComponentHelper::createComponent([
                        'type' => $types[$typeId],
                        'settings' => $this->typeSettings[$typeId] ?? [],
                    ], BaseLinkType::class);
                }
            }
        }

        return $this->linkTypes;
    }

    /**
     * Returns a Link control configured for the allowed link types.
     *
     * @param  string|list<string>  $path
     */
    public function control(string|array $path, mixed $value = null): LinkControl
    {
        $this->pickerConfigs ??= array_values(array_map(
            fn (BaseLinkType $type): array => $type->pickerConfig(),
            $this->linkTypes(),
        ));

        return LinkControl::make($path)
            ->types($this->pickerConfigs)
            ->showLabelField($this->showLabelField)
            ->advancedFields(array_values($this->advancedFields))
            ->value($value instanceof LinkData ? [...$value->serialize(), 'defaultLabel' => $value->getLabel(false)] : $value);
    }

    /**
     * Normalizes a URL string or a link array into a link.
     *
     * @throws InvalidArgumentException if the value names an unknown link type
     */
    public function normalize(mixed $value): ?LinkData
    {
        if ($value instanceof LinkData) {
            return $value;
        }

        $linkTypes = $this->linkTypes();

        if (! is_array($value)) {
            if (! $value) {
                return null;
            }

            $typeId = $this->resolveType($value);
            $linkType = $linkTypes[$typeId] ?? ComponentHelper::createComponent(Link::types()[$typeId], BaseLinkType::class);

            return new LinkData($value, $linkType);
        }

        $typeId = $value['type'] ?? UrlType::id();
        $destination = $value['value'] ?? $value[$typeId]['value'] ?? '';
        if (is_string($destination)) {
            $destination = trim($destination);
        }

        if (! $destination) {
            return null;
        }

        if (isset($linkTypes[$typeId])) {
            $linkType = $linkTypes[$typeId];
        } else {
            $type = Link::types()[$typeId] ?? null;
            if (! $type) {
                throw new InvalidArgumentException("Invalid link type: $typeId");
            }
            $linkType = ComponentHelper::createComponent($type, BaseLinkType::class);
        }

        $link = new LinkData($linkType->normalizeValue($destination), $linkType);
        if (! empty($value['label']) && $this->showLabelField) {
            $link->setLabel($value['label']);
        }

        foreach (['urlSuffix', 'target', 'title', 'ariaLabel'] as $attribute) {
            if (! empty($value[$attribute]) && in_array($attribute, $this->advancedFields)) {
                $link->$attribute = $value[$attribute];
            }
        }

        if ($link->urlSuffix !== null && ! str_starts_with($link->urlSuffix, '#')) {
            $link->urlSuffix = Str::start($link->urlSuffix, '?');
        }

        foreach (['class', 'rel'] as $attribute) {
            if (! empty($value[$attribute]) && in_array($attribute, $this->advancedFields)) {
                $link->$attribute = implode(' ', array_map(Html::id(...), explode(' ', (string) $value[$attribute])));
            }
        }

        if (! empty($value['id']) && in_array('id', $this->advancedFields)) {
            $link->id = Html::id($value['id']);
        }

        if (in_array('download', $this->advancedFields)) {
            $link->download = ! empty($value['download']);
            if (! empty($value['filename'])) {
                $link->setFilename($value['filename']);
            }
        }

        return $link;
    }

    /**
     * Points an element link at the same element in the given element’s site, if it exists there.
     */
    public function localize(LinkData $link, ElementInterface $element): LinkData
    {
        return $this->localizeAll([$link], $element)[0];
    }

    /**
     * Points element links at the same elements in the given element’s site, where they exist there. Values that
     * aren’t links are returned as they are.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, mixed>  $values
     * @return array<TKey, mixed>
     */
    public function localizeAll(array $values, ElementInterface $element): array
    {
        $linkedElements = array_map(function (mixed $value): ?ElementInterface {
            $linkedElement = $value instanceof LinkData ? $value->getElement() : null;

            return $linkedElement && $linkedElement::isLocalized() ? $linkedElement : null;
        }, $values);

        $idsByType = [];
        foreach (array_filter($linkedElements) as $linkedElement) {
            $idsByType[$linkedElement::class][] = $linkedElement->id;
        }

        $localizedIds = [];
        foreach ($idsByType as $elementType => $ids) {
            $localizedIds[$elementType] = array_flip($elementType::find()
                ->id(array_values(array_unique($ids)))
                ->siteId($element->siteId)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->revisions(null)
                ->ids());
        }

        foreach ($values as $key => $value) {
            $linkedElement = $linkedElements[$key];
            if (! $linkedElement || ! isset($localizedIds[$linkedElement::class][$linkedElement->id])) {
                continue;
            }

            /** @var LinkData $value */
            $values[$key] = $this->normalize([
                ...$value->serialize(),
                'value' => sprintf('{%s:%s@%s:url}', $linkedElement::refHandle(), $linkedElement->id, $element->siteId),
            ]) ?? $value;
        }

        return $values;
    }

    /**
     * Returns an error message if the link is invalid.
     */
    public function validate(LinkData $link, string $label): ?string
    {
        $linkTypes = $this->linkTypes();

        if (! isset($linkTypes[$link->getType()])) {
            $type = Link::types()[$link->getType()] ?? null;

            return t('{attribute} no longer allows {type} links.', [
                'attribute' => $label,
                'type' => is_subclass_of($type, BaseLinkType::class) ? $type::displayName() : $type,
            ]);
        }

        $value = $link->serialize()['value'] ?? '';
        $error = null;
        if (! $linkTypes[$link->getType()]->validateValue($value, $error)) {
            /** @var string|null $error */
            return $error ?? t('{attribute} is invalid.', [
                'attribute' => $label,
            ]);
        }

        if (mb_strlen($value) > $this->maxLength) {
            return t('{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.', [
                'attribute' => $label,
                'max' => $this->maxLength,
            ]);
        }

        return null;
    }

    public function isEmpty(LinkData $link): bool
    {
        $linkTypes = $this->linkTypes();
        $linkType = $linkTypes[$link->getType()] ?? $linkTypes[UrlType::id()] ?? new UrlType;

        return $linkType->isValueEmpty($link->serialize()['value'] ?? '');
    }

    /** @return list<int> */
    public function referenceTargetIds(?LinkData $link): array
    {
        $element = $link?->getElement();

        return $element ? [$element->id] : [];
    }

    /**
     * Returns the link value pointed at the new target, or `null` if the link doesn’t reference an old target.
     *
     * @param  int[]  $oldTargetIds
     * @return array<string, mixed>|null
     */
    public function replaceReference(?LinkData $link, array $oldTargetIds, int $newTargetId): ?array
    {
        $linkedElement = $link?->getElement();

        if (! $linkedElement || ! in_array($linkedElement->id, $oldTargetIds)) {
            return null;
        }

        return [
            ...$link->serialize(),
            'value' => sprintf('{%s:%s@%s:url}', $linkedElement::refHandle(), $newTargetId, $linkedElement->siteId),
        ];
    }

    private function resolveType(string $value): string
    {
        $linkTypes = $this->linkTypes();

        // check URL last, if it's selected
        $urlType = Arr::pull($linkTypes, UrlType::id());
        if ($urlType) {
            $linkTypes[UrlType::id()] = $urlType;
        }

        foreach ($linkTypes as $id => $linkType) {
            if ($linkType->supports($value)) {
                return $id;
            }
        }

        // See if any unselected types support it
        foreach (Link::types() as $typeId => $type) {
            if (! isset($linkTypes[$typeId]) && $type !== UrlType::class) {
                $linkType = ComponentHelper::createComponent($type, BaseLinkType::class);
                if ($linkType->supports($value)) {
                    return $linkType::id();
                }
            }
        }

        return UrlType::id();
    }
}
