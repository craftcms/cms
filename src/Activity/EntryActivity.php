<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use BackedEnum;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\EventTypes\ElementStatusChanged;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use DateTimeInterface;

use function CraftCms\Cms\t;

/**
 * @internal
 *
 * @since 6.0.0
 */
class EntryActivity
{
    /** @var array<string, string> */
    private const array Attributes = [
        'title' => 'Title',
        'slug' => 'Slug',
        'enabled' => 'Enabled',
        'enabledForSite' => 'Enabled for site',
        'postDate' => 'Post Date',
        'expiryDate' => 'Expiry Date',
        'authorIds' => 'Authors',
    ];

    public static function original(Entry $entry): ?Entry
    {
        return Entry::find()
            ->id($entry->id)
            ->siteId($entry->siteId)
            ->status(null)
            ->one();
    }

    public static function recordCreated(Entry $entry): void
    {
        Activities::record(new ElementCreated(
            subject: $entry,
            site: Sites::getSiteById($entry->siteId),
        ));
    }

    /**
     * @param  array<string, mixed>  $originalFieldValues  The original’s dirty field values, from fieldValues()
     * @param  string[]  $dirtyAttributes
     * @param  string[]  $dirtyFields
     */
    public static function recordUpdated(
        Entry $entry,
        Entry $original,
        array $originalFieldValues,
        array $dirtyAttributes,
        array $dirtyFields,
        bool $authorChanged = true,
    ): void {
        if (! $authorChanged) {
            $dirtyAttributes = array_values(array_diff($dirtyAttributes, ['authorIds']));
        }

        [$changes, $contentChanged] = self::changes($entry, $original, $originalFieldValues, $dirtyAttributes, $dirtyFields);
        $oldStatus = $original->getStatus();
        $newStatus = $entry->getStatus();

        if ($oldStatus === $newStatus && ! $contentChanged) {
            return;
        }

        $site = Sites::getSiteById($entry->siteId);
        $event = $oldStatus === $newStatus
            ? new ElementUpdated(subject: $entry, site: $site, changes: $changes)
            : new ElementStatusChanged(
                subject: $entry,
                site: $site,
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                changes: $changes,
            );

        Activities::record($event);
    }

    /**
     * Returns the comparable values of an entry’s dirty fields.
     *
     * Capture the original’s values before saving: nested and related elements load lazily,
     * and saving the entry can update them in place.
     *
     * @param  string[]  $dirtyFields
     * @return array<string, mixed>
     */
    public static function fieldValues(Entry $entry, array $dirtyFields): array
    {
        $values = [];

        foreach ($entry->getFieldLayout()?->getCustomFields() ?? [] as $field) {
            if (! in_array($field->handle, $dirtyFields, true)) {
                continue;
            }

            $value = $entry->getFieldValue($field->handle);
            $values[$field->handle] = $field instanceof BaseRelationField
                ? self::relationValues($value)
                : $field->serializeValue($value, $entry);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $originalFieldValues  The original’s dirty field values, from fieldValues()
     * @param  string[]  $dirtyAttributes
     * @param  string[]  $dirtyFields
     * @return array{list<ActivityChange>, bool}
     */
    public static function changes(
        Entry $entry,
        Entry $original,
        array $originalFieldValues,
        array $dirtyAttributes,
        array $dirtyFields,
    ): array {
        $changes = [];
        $contentChanged = false;

        foreach (self::Attributes as $attribute => $label) {
            if (! in_array($attribute, $dirtyAttributes, true)) {
                continue;
            }

            [$old, $new] = $attribute === 'authorIds'
                ? self::authorValues($entry, $original)
                : [self::attributeValue($original, $attribute), self::attributeValue($entry, $attribute)];
            self::appendChange($changes, $contentChanged, t($label), $old, $new);
        }

        $newFieldValues = self::fieldValues($entry, $dirtyFields);

        foreach ($entry->getFieldLayout()?->getCustomFields() ?? [] as $field) {
            if (! array_key_exists((string) $field->handle, $newFieldValues)) {
                continue;
            }

            self::appendChange(
                $changes,
                $contentChanged,
                t($field->name, category: 'site'),
                $originalFieldValues[$field->handle] ?? null,
                $newFieldValues[$field->handle],
            );
        }

        return [$changes, $contentChanged];
    }

    /** @param list<ActivityChange> $changes */
    private static function appendChange(
        array &$changes,
        bool &$contentChanged,
        string $label,
        mixed $old,
        mixed $new,
    ): void {
        if ($old === $new) {
            return;
        }

        $oldSafe = self::normalizeSafeValue($old);
        $newSafe = self::normalizeSafeValue($new);

        if ($oldSafe && $newSafe && $old === $new) {
            return;
        }

        $contentChanged = true;

        if (! $oldSafe || ! $newSafe) {
            return;
        }

        $changes[] = new ActivityChange($label, $old, $new);
    }

    private static function attributeValue(Entry $entry, string $attribute): mixed
    {
        return match ($attribute) {
            'enabledForSite' => $entry->getEnabledForSite(),
            default => $entry->{$attribute},
        };
    }

    /** @return array{list<array<string, mixed>>, list<array<string, mixed>>} */
    private static function authorValues(Entry $entry, Entry $original): array
    {
        $oldIds = $original->getAuthorIds();
        $newIds = $entry->getAuthorIds();
        $ids = array_values(array_unique([...$oldIds, ...$newIds]));
        $authors = $ids === []
            ? collect()
            : User::find()->id($ids)->status(null)->collect()->keyBy('id');

        return [
            self::elementValues(collect($oldIds)->map($authors->get(...))->filter()->all()),
            self::elementValues(collect($newIds)->map($authors->get(...))->filter()->all()),
        ];
    }

    /**
     * @param  ElementQueryInterface|ElementCollection<int, ElementInterface>  $value
     * @return list<array<string, mixed>>
     */
    private static function relationValues(ElementQueryInterface|ElementCollection $value): array
    {
        return self::elementValues($value instanceof ElementQueryInterface
            ? (clone $value)->status(null)->all()
            : $value->all());
    }

    /**
     * @param  list<ElementInterface>  $elements
     * @return list<array<string, mixed>>
     */
    private static function elementValues(array $elements): array
    {
        return array_map(fn (ElementInterface $element): array => [
            'elementType' => $element::class,
            'id' => $element->id,
            'label' => $element->getUiLabel(),
            'siteId' => $element->siteId,
        ], $elements);
    }

    private static function normalizeSafeValue(mixed &$value): bool
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            $value = $value->format(DateTimeInterface::ATOM);
        }

        if (is_string($value)) {
            return mb_check_encoding($value) && strip_tags($value) === $value;
        }

        if (is_float($value)) {
            return is_finite($value);
        }

        if (is_int($value) || is_bool($value) || $value === null) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        return array_all(array_keys($value), function ($key) use (&$value): bool {
            return self::normalizeSafeValue($value[$key]);
        });
    }
}
