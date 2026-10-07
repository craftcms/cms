<?php

declare(strict_types=1);

namespace craft\mcp\elements;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\mcp\elements\adapters\AddressAdapter;
use craft\mcp\elements\adapters\AssetAdapter;
use craft\mcp\elements\adapters\EntryAdapter;
use craft\mcp\elements\adapters\UserAdapter;
use yii\base\Component;
use yii\base\InvalidArgumentException;

/** Registers element adapters exposed through the elements.* tools. */
class ElementAdapterRegistry extends Component
{
    public const EVENT_REGISTER_ADAPTERS = 'registerAdapters';

    private ?array $_types = null;

    /** @return list<class-string<ElementAdapter>> */
    public function types(): array
    {
        if ($this->_types === null) {
            $event = new RegisterComponentTypesEvent([
                'types' => [EntryAdapter::class, AssetAdapter::class, UserAdapter::class, AddressAdapter::class],
            ]);
            $this->trigger(self::EVENT_REGISTER_ADAPTERS, $event);
            $this->_types = [];

            foreach ($event->types as $type) {
                $this->register($type);
            }
        }

        return array_values($this->_types);
    }

    /** @param class-string<ElementAdapter> $type */
    public function register(string $type): void
    {
        if (!is_a($type, ElementAdapter::class, true)) {
            throw new InvalidArgumentException('Element adapters must implement ' . ElementAdapter::class . '.');
        }

        if ($this->_types === null) {
            $this->types();
        }

        $this->_types[$type::handle()] = $type;
    }

    /** @return list<string> */
    public function handles(): array
    {
        return array_map(static fn(string $type): string => $type::handle(), $this->types());
    }

    public function find(string $handle): ?ElementAdapter
    {
        foreach ($this->types() as $type) {
            if ($type::handle() === $handle || $type::elementType() === $handle) {
                return Craft::$app->get('mcp')->getCapabilityContainer()->get($type);
            }
        }

        return null;
    }
}
