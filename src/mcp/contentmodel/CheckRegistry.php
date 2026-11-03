<?php

namespace craft\mcp\contentmodel;

use Craft;
use craft\mcp\contentmodel\checks\DuplicateFieldCandidates;
use craft\mcp\contentmodel\checks\EmptyEntryTypes;
use craft\mcp\contentmodel\checks\EmptySections;
use craft\mcp\contentmodel\checks\MissingAltText;
use craft\mcp\contentmodel\checks\UnusedEntryTypes;
use craft\mcp\contentmodel\checks\UnusedFields;
use InvalidArgumentException;
use LogicException;

/**
 * Registers content-model audit checks by their IDs.
 *
 * @since 5.12.0
 */
class CheckRegistry
{
    /** @var array<string, class-string<Check>> */
    private array $checks = [];

    public function __construct()
    {
        foreach ([DuplicateFieldCandidates::class, EmptyEntryTypes::class, EmptySections::class, MissingAltText::class, UnusedEntryTypes::class, UnusedFields::class] as $type) {
            $this->register($type);
        }
    }

    /** @param class-string<Check> $type */
    public function register(string $type): void
    {
        if (!is_a($type, Check::class, true)) {
            throw new InvalidArgumentException("Content-model check [$type] must implement " . Check::class . '.');
        }

        $id = $type::id();
        if (isset($this->checks[$id])) {
            throw new LogicException("Content-model check [$id] is already registered.");
        }

        $this->checks[$id] = $type;
    }

    /** @return list<string> */
    public function ids(): array
    {
        return array_keys($this->checks);
    }

    public function find(string $id): ?Check
    {
        $type = $this->checks[$id] ?? null;

        return $type === null ? null : Craft::$app->get('mcp')->getCapabilityContainer()->get($type);
    }
}
