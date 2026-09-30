<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Data;

use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Color data sampled from an image.
 *
 * @see Images::colors()
 *
 * @implements Arrayable<string, mixed>
 */
#[AllowedInSandbox]
readonly class ImageColors implements Arrayable, JsonSerializable
{
    /**
     * @param  string|null  $dominant  The image’s dominant color as a hex string (e.g. `#3a6ea5`), or `null` if it
     *                                 couldn’t be determined
     * @param  list<list<string>>  $grid  The average colors of the image’s regions, as rows of hex strings from top
     *                                    to bottom, each listed from left to right. Colors of regions that aren’t
     *                                    fully opaque include an alpha channel (e.g. `#3a6ea580`). Empty if the image
     *                                    couldn’t be sampled.
     */
    public function __construct(
        #[AllowedInSandbox]
        public ?string $dominant = null,
        #[AllowedInSandbox]
        public array $grid = [],
    ) {}

    /**
     * Creates an instance from stored color data.
     *
     * Anything that isn’t a hex color is dropped, so the colors are always safe to output in CSS.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $dominant = $data['dominant'] ?? null;
        $grid = [];

        foreach (is_array($data['grid'] ?? null) ? $data['grid'] : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $row = array_values(array_filter($row, self::isHexColor(...)));

            if ($row !== []) {
                $grid[] = $row;
            }
        }

        return new self(
            dominant: self::isHexColor($dominant) ? $dominant : null,
            grid: $grid,
        );
    }

    /**
     * Returns the average color of the image’s left edge, as a hex string, or `null` if there’s no grid.
     */
    #[AllowedInSandbox]
    public function left(): ?string
    {
        return $this->averageColor(array_map(fn (array $row): string => $row[0], $this->grid));
    }

    /**
     * Returns the average color of the image’s right edge, as a hex string, or `null` if there’s no grid.
     */
    #[AllowedInSandbox]
    public function right(): ?string
    {
        return $this->averageColor(array_map(array_last(...), $this->grid));
    }

    /**
     * Returns the average color of the image’s top edge, as a hex string, or `null` if there’s no grid.
     */
    #[AllowedInSandbox]
    public function top(): ?string
    {
        return $this->averageColor($this->grid[0] ?? []);
    }

    /**
     * Returns the average color of the image’s bottom edge, as a hex string, or `null` if there’s no grid.
     */
    #[AllowedInSandbox]
    public function bottom(): ?string
    {
        return $this->averageColor(array_last($this->grid) ?? []);
    }

    /**
     * Averages colors’ red, green, and blue channels. Alpha channels are ignored.
     *
     * @param  list<string>  $colors
     */
    private function averageColor(array $colors): ?string
    {
        if ($colors === []) {
            return null;
        }

        $channels = array_map(
            fn (int $offset): int => (int) round(array_sum(array_map(
                fn (string $color): int => (int) hexdec(substr($color, $offset, 2)),
                $colors,
            )) / count($colors)),
            [1, 3, 5],
        );

        return sprintf('#%02x%02x%02x', ...$channels);
    }

    private static function isHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}([0-9a-f]{2})?$/i', $value) === 1;
    }

    /** @return array{dominant: string|null, grid: list<list<string>>} */
    public function toArray(): array
    {
        return [
            'dominant' => $this->dominant,
            'grid' => $this->grid,
        ];
    }

    /** @return array{dominant: string|null, grid: list<list<string>>} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
