<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugin;
use InvalidArgumentException;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasEditions
{
    /** @var Edition The minimum required Craft CMS edition. */
    public Edition $minCmsEdition = Edition::Solo;

    /** @var string The active edition. */
    public string $edition = 'standard';

    /** {@inheritdoc} */
    public static function editions(): array
    {
        return [
            'standard',
        ];
    }

    /** {@inheritdoc} */
    public function is(string $edition, string $operator = '='): bool
    {
        $editions = static::editions();
        $activeIndex = array_search($this->edition, $editions, true);
        $otherIndex = array_search($edition, $editions, true);

        throw_if($otherIndex === false, new InvalidArgumentException('Unsupported edition: '.$edition));

        return match ($operator) {
            '<', 'lt' => $activeIndex < $otherIndex,
            '<=', 'le' => $activeIndex <= $otherIndex,
            '>', 'gt' => $activeIndex > $otherIndex,
            '>=', 'ge' => $activeIndex >= $otherIndex,
            '==', '=', 'eq' => $activeIndex === $otherIndex,
            '!=', '<>', 'ne' => $activeIndex !== $otherIndex,
            default => throw new InvalidArgumentException('Invalid edition comparison operator: '.$operator),
        };
    }
}
