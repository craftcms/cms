<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\RegisterElementTypes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;

#[Singleton]
class ElementTypes
{
    /**
     * @var string[]
     */
    private array $elementTypesByRefHandle = [];

    /**
     * Returns the class of an element with a given ID.
     *
     * @param  int  $elementId  The element’s ID
     * @return class-string<ElementInterface>|null The element’s class, or null if it could not be found
     */
    public function getElementTypeById(int $elementId): ?string
    {
        return $this->getElementTypeByKey('id', $elementId);
    }

    /**
     * Returns the class of an element with a given UID.
     *
     * @param  string  $uid  The element’s UID
     * @return string|null The element’s class, or null if it could not be found
     */
    public function getElementTypeByUid(string $uid): ?string
    {
        return $this->getElementTypeByKey('uid', $uid);
    }

    /**
     * Returns the class of an element with a given ID/UID.
     *
     * @param  string  $property  Either `id` or `uid`
     * @param  int|string  $elementId  The element’s ID/UID
     * @return string|null The element’s class, or null if it could not be found
     */
    public function getElementTypeByKey(string $property, int|string $elementId): ?string
    {
        return DB::table(Table::ELEMENTS)
            ->where($property, $elementId)
            ->value('type');
    }

    /**
     * Returns the classes of elements with the given IDs.
     *
     * @param  int[]  $elementIds  The elements’ IDs
     * @return string[]
     */
    public function getElementTypesByIds(array $elementIds): array
    {
        return DB::table(Table::ELEMENTS)
            ->whereIn('id', $elementIds)
            ->distinct()
            ->pluck('type')
            ->all();
    }

    /**
     * Returns all available element classes.
     *
     * @return class-string<ElementInterface>[] The available element classes.
     */
    public function getAllElementTypes(): array
    {
        $elementTypes = [
            Address::class,
            Asset::class,
            Entry::class,
            User::class,
        ];

        event($event = new RegisterElementTypes($elementTypes));

        return $event->types;
    }

    /**
     * Returns an element class by its handle.
     *
     * @param  string  $refHandle  The element class handle
     * @return string|null The element class, or null if it could not be found
     */
    public function getElementTypeByRefHandle(string $refHandle): ?string
    {
        if (! isset($this->elementTypesByRefHandle[$refHandle])) {
            $class = $this->elementTypeByRefHandle($refHandle);

            // Special cases for categories/tags/globals, if they've been removed
            if ($class === false && in_array($refHandle, ['category', 'tag', 'globalset'])) {
                $class = Entry::class;
            }

            $this->elementTypesByRefHandle[$refHandle] = $class;
        }

        return $this->elementTypesByRefHandle[$refHandle] ?: null;
    }

    private function elementTypeByRefHandle(string $refHandle): string|false
    {
        if (is_subclass_of($refHandle, ElementInterface::class)) {
            return $refHandle;
        }

        foreach ($this->getAllElementTypes() as $class) {
            if (
                ($elementRefHandle = $class::refHandle()) !== null &&
                strcasecmp($elementRefHandle, $refHandle) === 0
            ) {
                return $class;
            }
        }

        return false;
    }
}
