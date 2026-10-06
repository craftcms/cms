<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Fixtures\SinceTags;

class MissingDocblock {}

/**
 * A class with a documented method.
 */
class MethodTagOnly
{
    /**
     * @since 6.0.0
     */
    public function documented(): void {}
}

/**
 * @since
 */
class EmptySinceTag {}

/**
 * @since 6.0.0
 */
class CurrentVersion {}

/**
 * @since 6.1.0
 */
class FutureVersion {}

new class {};

interface UndocumentedInterface {}

trait UndocumentedTrait {}

enum UndocumentedEnum {}
