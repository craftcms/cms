<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use BackedEnum;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\Sites;
use DateTimeInterface;

use function CraftCms\Cms\t;

/** @internal */
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

    public static function shouldRecord(Entry $entry, bool $recordActivity): bool
    {
        return ElementActivity::shouldRecordWrite($entry, $recordActivity);
    }

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
        Activities::record(
            'craft.element.created',
            subject: $entry,
            site: Sites::getSiteById($entry->siteId),
        );
    }

    /**
     * @param  string[]  $dirtyAttributes
     * @param  string[]  $dirtyFields
     */
    public static function recordUpdated(
        Entry $entry,
        Entry $original,
        array $dirtyAttributes,
        array $dirtyFields,
    ): void {
        [$changes, $contentChanged] = self::changes($entry, $original, $dirtyAttributes, $dirtyFields);
        $oldStatus = $original->getStatus();
        $newStatus = $entry->getStatus();

        if ($oldStatus === $newStatus && ! $contentChanged) {
            return;
        }

        Activities::record(
            $oldStatus === $newStatus ? 'craft.element.updated' : 'craft.element.status-changed',
            subject: $entry,
            site: Sites::getSiteById($entry->siteId),
            data: $oldStatus === $newStatus ? [] : compact('oldStatus', 'newStatus'),
            changes: $changes,
        );
    }

    /**
     * @param  string[]  $dirtyAttributes
     * @param  string[]  $dirtyFields
     * @return array{list<array{type:string, id:string, label:string, old:mixed, new:mixed}>, bool}
     */
    private static function changes(
        Entry $entry,
        Entry $original,
        array $dirtyAttributes,
        array $dirtyFields,
    ): array {
        $changes = [];
        $contentChanged = false;

        foreach (self::Attributes as $attribute => $label) {
            if (! in_array($attribute, $dirtyAttributes, true)) {
                continue;
            }

            $old = self::attributeValue($original, $attribute);
            $new = self::attributeValue($entry, $attribute);

            if ($old === $new) {
                continue;
            }

            $oldSafe = self::normalizeSafeValue($old);
            $newSafe = self::normalizeSafeValue($new);

            if ($oldSafe && $newSafe && $old === $new) {
                continue;
            }

            $contentChanged = true;

            if (! $oldSafe || ! $newSafe) {
                continue;
            }

            $changes[] = [
                'type' => 'attribute',
                'id' => $attribute,
                'label' => t($label),
                'old' => $old,
                'new' => $new,
            ];
        }

        foreach ($entry->getFieldLayout()?->getCustomFields() ?? [] as $field) {
            if (! in_array($field->handle, $dirtyFields, true)) {
                continue;
            }

            $old = $field->serializeValue($original->getFieldValue($field->handle), $original);
            $new = $field->serializeValue($entry->getFieldValue($field->handle), $entry);

            if ($old === $new) {
                continue;
            }

            $oldSafe = self::normalizeSafeValue($old);
            $newSafe = self::normalizeSafeValue($new);

            if ($oldSafe && $newSafe && $old === $new) {
                continue;
            }

            $contentChanged = true;

            if (! $oldSafe || ! $newSafe) {
                continue;
            }

            $changes[] = [
                'type' => 'field',
                'id' => $field->layoutElement->uid,
                'label' => t($field->name, category: 'site'),
                'old' => $old,
                'new' => $new,
            ];
        }

        return [$changes, $contentChanged];
    }

    private static function attributeValue(Entry $entry, string $attribute): mixed
    {
        return match ($attribute) {
            'enabledForSite' => $entry->getEnabledForSite(),
            'authorIds' => $entry->getAuthorIds(),
            default => $entry->{$attribute},
        };
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

        return array_all($value, fn ($item) => self::normalizeSafeValue($item));
    }
}
