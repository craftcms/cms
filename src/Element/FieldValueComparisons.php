<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use BackedEnum;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Data\FieldValueComparison;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Json;
use DateTimeInterface;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use Stringable;
use Symfony\Component\Yaml\Yaml;

use function CraftCms\Cms\t;

/**
 * Compares a field layout element’s value on a draft, revision, or nested element with the value it was based on,
 * for the “Compare changes” field action.
 *
 * - A draft, or an element nested in one, is compared with the current element.
 * - A revision, or an element nested in one, is compared with the revision before it.
 * - An outdated field is compared the other way around: the current element before it last changed the field,
 *   with the current element. That’s the change that was merged into the draft.
 *
 * Values are read the way the activity timeline reads them: relations become element references, and other
 * values are serialized. Rich text is reduced to its text, and nested elements (Matrix entries, Content Blocks,
 * addresses) are listed with their own fields, so changes inside them show up in a text diff.
 *
 * @internal
 *
 * @since 6.0.0
 */
#[Singleton]
readonly class FieldValueComparisons
{
    /** How many levels of nested elements list their own fields. Deeper ones are listed by title. */
    private const int NestedDepth = 3;

    /**
     * Returns whether an element’s field values can be compared with the ones it was based on.
     */
    public static function isComparable(ElementInterface $element): bool
    {
        return $element->id !== null
            && ! $element->getIsCanonical()
            && ! $element->getIsUnpublishedDraft();
    }

    public function compare(ElementInterface $element, BaseField $layoutElement, AttributeStatus $status): FieldValueComparison
    {
        if ($status === AttributeStatus::Outdated && ! $element->getIsRevision()) {
            $current = $element->getCanonical();
            $before = $this->revisionBeforeChange($current, $layoutElement);

            return new FieldValueComparison(
                fromLabel: $before ? $this->label($before) : t('Earlier version'),
                from: $before ? $this->value($before, $layoutElement) : null,
                toLabel: t('Current'),
                to: $this->value($current, $layoutElement),
                hasBase: $before !== null,
            );
        }

        if ($element->getIsRevision()) {
            // An unchanged nested element can be shared by its owner’s revisions, so its base can be itself.
            [$base, $previous] = $this->previousRevision($element);

            return new FieldValueComparison(
                fromLabel: $previous ? $this->label($previous) : t('No earlier revision'),
                from: $base ? $this->value($base, $layoutElement) : null,
                toLabel: $this->label($element),
                to: $this->value($element, $layoutElement),
                hasBase: $previous !== null,
            );
        }

        $current = $element->getCanonical();

        return new FieldValueComparison(
            fromLabel: t('Current'),
            from: $this->value($current, $layoutElement),
            toLabel: $this->label($element),
            to: $this->value($element, $layoutElement),
        );
    }

    /**
     * Returns what an element is called in a comparison: “Current”, a revision, or a draft.
     */
    private function label(ElementInterface $element): string
    {
        $root = $element->getRootOwner();

        return match (true) {
            $root->getIsRevision() => t('Revision {num}', ['num' => $root->revisionNum]),
            $root->getIsDraft() && $root->isProvisionalDraft => t('Your changes'),
            $root->getIsDraft() => $root->draftName ?: t('Draft'),
            default => t('Current'),
        };
    }

    /**
     * Returns the revision before a revision, and the revision it belongs to. For an element nested in a revision,
     * that’s the same element in the owner’s previous revision, which may not have it.
     *
     * @return array{?ElementInterface, ?ElementInterface}
     */
    private function previousRevision(ElementInterface $revision): array
    {
        $owner = $revision instanceof NestedElementInterface ? $revision->getOwner() : null;

        if ($owner?->getIsRevision()) {
            [$previousOwner, $previous] = $this->previousRevision($owner);

            if ($previousOwner === null) {
                return [null, $previous];
            }

            $query = $revision::find();

            if (! $query instanceof NestedElementQueryInterface) {
                return [null, $previous];
            }

            $base = array_find(
                $query
                    ->ownerId($previousOwner->id)
                    ->fieldId($revision->getField()?->id)
                    ->siteId($revision->siteId)
                    ->revisions(null)
                    ->status(null)
                    ->all(),
                fn (ElementInterface $element): bool => $element->getCanonicalId() === $revision->getCanonicalId(),
            );

            return [$base, $previous];
        }

        $previous = $revision::find()
            ->revisionOf($revision->getCanonicalId())
            ->siteId($revision->siteId)
            ->status(null)
            ->where('revisions.num', '<', $revision->revisionNum)
            ->orderByDesc('revisions.num')
            ->one();

        return [$previous, $previous];
    }

    /**
     * Returns the current element’s last revision from before it last changed a field.
     */
    private function revisionBeforeChange(ElementInterface $current, BaseField $layoutElement): ?ElementInterface
    {
        $query = $layoutElement instanceof CustomField
            ? DB::table(Table::CHANGEDFIELDS)
                ->where('fieldId', $layoutElement->getField()->id)
                ->where('layoutElementUid', $layoutElement->uid)
            : DB::table(Table::CHANGEDATTRIBUTES)
                ->where('attribute', $layoutElement->attribute());

        $changedAt = $query
            ->where('elementId', $current->id)
            ->where('siteId', $current->siteId)
            ->value('dateUpdated');

        if ($changedAt === null) {
            return null;
        }

        return $current::find()
            ->revisionOf($current->id)
            ->siteId($current->siteId)
            ->status(null)
            ->where('elements.dateCreated', '<', $changedAt)
            ->orderByDesc('revisions.num')
            ->one();
    }

    private function value(ElementInterface $element, BaseField $layoutElement): mixed
    {
        if ($layoutElement instanceof CustomField) {
            return $this->fieldValue($element, $layoutElement->getField());
        }

        $attribute = $layoutElement->attribute();

        return match (true) {
            $attribute === 'authorIds' && $element instanceof Entry => $this->elementReferences($element->getAuthors()),
            $attribute === 'enabledForSite' => $element->getEnabledForSite(),
            default => $this->normalize($element->$attribute ?? null),
        };
    }

    private function fieldValue(ElementInterface $element, FieldInterface $field): mixed
    {
        $value = $element->getFieldValue((string) $field->handle);

        if ($field instanceof BaseRelationField) {
            return $this->elementReferences($this->elements($value));
        }

        if ($value instanceof ElementQueryInterface || $value instanceof ElementCollection) {
            return $this->nestedElementsText($this->elements($value));
        }

        return $this->normalize($field->serializeValue($value, $element));
    }

    /**
     * Lists nested elements as text, separated by blank lines: each element’s type (or label, for elements without
     * types), followed by a “Label: value” line for its title and each of its fields.
     *
     * @param  list<ElementInterface>  $elements
     */
    private function nestedElementsText(array $elements): string
    {
        $blocks = array_map(
            fn (ElementInterface $element): string => implode("\n", [
                $this->nestedElementName($element),
                ...$this->nestedElementLines($element, [], 1),
            ]),
            $elements,
        );

        return implode("\n\n", $blocks);
    }

    /**
     * Returns “Label: value” lines for a nested element’s title and fields. Lines for elements nested further down
     * start with the path to them, such as “Inner blocks › Child 2 › Title: …”, so they can be told apart without
     * indentation.
     *
     * @param  list<string>  $path
     * @return list<string>
     */
    private function nestedElementLines(ElementInterface $element, array $path, int $depth): array
    {
        $lines = [];

        if ($element instanceof Entry && $element->getType()->hasTitleField && filled($element->title)) {
            $titleField = $element->getFieldLayout()?->getFirstVisibleElementByType(TitleField::class, $element);
            $label = $titleField instanceof TitleField ? $titleField->label() : null;
            $lines[] = $this->nestedLine([...$path, $label ?: t('Title')], $element->title);
        }

        foreach ($element->getFieldLayout()?->getCustomFields() ?? [] as $field) {
            $label = t((string) $field->name, category: 'site');
            $value = $field instanceof BaseRelationField ? null : $element->getFieldValue((string) $field->handle);

            if (! $value instanceof ElementQueryInterface && ! $value instanceof ElementCollection) {
                $text = $this->text($this->fieldValue($element, $field));

                if ($text !== '') {
                    $lines[] = $this->nestedLine([...$path, $label], $text);
                }

                continue;
            }

            $children = $this->elements($value);

            if ($depth >= self::NestedDepth) {
                $titles = array_map(fn (ElementInterface $child): string => $child->getUiLabel(), $children);

                if ($titles !== []) {
                    $lines[] = $this->nestedLine([...$path, $label], implode(', ', $titles));
                }

                continue;
            }

            foreach ($children as $i => $child) {
                $childPath = [...$path, $label, $this->nestedElementName($child, count($children) > 1 ? $i + 1 : null)];
                $childLines = $this->nestedElementLines($child, $childPath, $depth + 1);
                array_push($lines, ...($childLines ?: [implode(' › ', $childPath)]));
            }
        }

        return $lines;
    }

    /**
     * Returns what a nested element is called: its type, or its label for elements without types, numbered when
     * there are several, and noting when it’s disabled.
     */
    private function nestedElementName(ElementInterface $element, ?int $number = null): string
    {
        $name = $element instanceof Entry
            ? t($element->getType()->name, category: 'site')
            : $element->getUiLabel();

        if ($number !== null) {
            $name .= " $number";
        }

        if (! $element->enabled) {
            $name .= ' ('.t('Disabled').')';
        }

        return $name;
    }

    /**
     * @param  list<string>  $path  The labels leading to the value, ending with its field’s label.
     */
    private function nestedLine(array $path, string $text): string
    {
        return implode(' › ', $path).': '.$text;
    }

    /**
     * Returns a normalized value as text, for listing it within a nested element.
     */
    private function text(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? t('Yes') : t('No'),
            is_array($value) => implode(', ', array_column($value, 'label')),
            default => trim((string) $value),
        };
    }

    /**
     * @return list<ElementInterface>
     */
    private function elements(mixed $value): array
    {
        return match (true) {
            $value instanceof ElementQueryInterface => (clone $value)->status(null)->all(),
            $value instanceof ElementCollection => array_values($value->all()),
            $value instanceof ElementInterface => [$value],
            default => [],
        };
    }

    /**
     * @param  iterable<ElementInterface>  $elements
     * @return list<array{elementType: class-string<ElementInterface>, id: int|null, label: string, siteId: int|null}>
     */
    private function elementReferences(iterable $elements): array
    {
        $references = [];

        foreach ($elements as $element) {
            $references[] = [
                'elementType' => $element::class,
                'id' => $element->id,
                'label' => (string) $element->getUiLabel(),
                'siteId' => $element->siteId,
            ];
        }

        return $references;
    }

    /**
     * Turns a serialized value into a string, boolean, or `null`.
     */
    private function normalize(mixed $value): string|bool|null
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return match (true) {
            $value === null, $value === '', $value === [] => null,
            is_bool($value) => $value,
            $value instanceof DateTimeInterface => I18N::getFormatter()->asDateTime($value, 'short'),
            is_string($value) => $this->htmlToText($value),
            is_scalar($value) => (string) $value,
            is_array($value) => rtrim(Yaml::dump($value, 4, 2)),
            $value instanceof Stringable => $this->htmlToText((string) $value),
            default => Json::encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: null,
        };
    }

    /**
     * Reduces HTML to its text: block ends become line breaks, and list items get bullets.
     */
    private function htmlToText(string $value): string
    {
        if (strip_tags($value) === $value) {
            return $value;
        }

        $text = preg_replace('/<li\b[^>]*>/i', '• ', $value) ?? $value;
        $text = preg_replace('/<br\s*\/?>|<\/(p|div|li|h[1-6]|blockquote|pre|tr|figure)>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }
}
