<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Data;

use CraftCms\Cms\Image\ColorGrid;
use CraftCms\Cms\Image\ColorNames;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Color data sampled from an image.
 *
 * @see Images::colors()
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
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
     * @param  list<string>  $palette  The image’s main colors as hex strings, most dominant first. Empty if the image
     *                                 couldn’t be sampled, or was sampled before palettes were.
     * @param  list<list<array{float, float}|null>>  $luminance  How light the darker and lighter parts of each of the
     *                                                           grid’s regions are, as the 10th and 90th percentiles of
     *                                                           their pixels’ WCAG relative luminance, from 0 (black) to
     *                                                           1 (white). `null` for regions with no mostly opaque
     *                                                           pixels. Empty if the image couldn’t be sampled, or was
     *                                                           sampled before luminance was.
     * @param  list<float>  $weights  How much of the image each palette color covers, as a share of everything the
     *                                palette covers, from 0 to 1, in the same order as `palette`. Empty if the image
     *                                was sampled before weights were.
     */
    public function __construct(
        #[AllowedInSandbox]
        public ?string $dominant = null,
        #[AllowedInSandbox]
        public array $grid = [],
        #[AllowedInSandbox]
        public array $palette = [],
        #[AllowedInSandbox]
        public array $luminance = [],
        #[AllowedInSandbox]
        public array $weights = [],
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

        $palette = array_values(array_filter(is_array($data['palette'] ?? null) ? $data['palette'] : [], self::isHexColor(...)));

        return new self(
            dominant: self::isHexColor($dominant) ? $dominant : null,
            grid: $grid,
            palette: $palette,
            luminance: self::luminanceFromArray($data['luminance'] ?? null),
            weights: self::weightsFromArray($data['weights'] ?? null, count($palette)),
        );
    }

    /**
     * Returns the weights if there’s one between 0 and 1 for each palette color, or else none, since they only mean
     * anything alongside the colors they’re for.
     *
     * @return list<float>
     */
    private static function weightsFromArray(mixed $weights, int $count): array
    {
        if (! is_array($weights) || ! array_is_list($weights) || count($weights) !== $count) {
            return [];
        }

        foreach ($weights as $weight) {
            if ((! is_int($weight) && ! is_float($weight)) || $weight < 0 || $weight > 1) {
                return [];
            }
        }

        return array_map(floatval(...), $weights);
    }

    /**
     * @return list<list<array{float, float}|null>>
     */
    private static function luminanceFromArray(mixed $luminance): array
    {
        if (! is_array($luminance)) {
            return [];
        }

        $rows = [];

        foreach ($luminance as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = array_values(array_map(static function (mixed $range): ?array {
                if (! is_array($range) || count($range) !== 2 || ! is_numeric($range[0] ?? null) || ! is_numeric($range[1] ?? null)) {
                    return null;
                }

                $values = [max(0.0, min(1.0, (float) $range[0])), max(0.0, min(1.0, (float) $range[1]))];
                sort($values);

                return [$values[0], $values[1]];
            }, $row));
        }

        return $rows;
    }

    /**
     * Returns the names of the image’s main colors, such as `red` or `teal`, most dominant first, without repeats.
     *
     * @return list<string>
     *
     * @see ColorNames
     */
    #[AllowedInSandbox]
    public function labels(): array
    {
        return array_values(array_unique(array_map(ColorNames::name(...), $this->palette)));
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
     * Returns whether white or black text is more legible over part of the image, and how well it contrasts.
     *
     * White text is compared with the lighter parts of the image there, and black text with the darker parts, since
     * those are where it’s hardest to read. A contrast ratio under 4.5 means neither is legible enough on its own,
     * such as over a busy part of a photo, so the text needs a scrim or a shadow behind it.
     *
     * Returns `null` if the image hasn’t been sampled for luminance, or the part has no mostly opaque regions.
     *
     * @param  'top'|'bottom'|'left'|'right'|null  $region  The edge the text sits along, or `null` for anywhere in the
     *                                                      image
     * @return array{color: '#ffffff'|'#000000', contrast: float}|null The text color, and its WCAG contrast ratio
     *                                                                 with the part of the image behind it
     */
    #[AllowedInSandbox]
    public function textColor(?string $region = null): ?array
    {
        $rows = match ($region) {
            'top' => array_slice($this->luminance, 0, 1),
            'bottom' => array_slice($this->luminance, -1),
            'left' => array_map(fn (array $row): array => array_slice($row, 0, 1), $this->luminance),
            'right' => array_map(fn (array $row): array => array_slice($row, -1), $this->luminance),
            null => $this->luminance,
            default => throw new InvalidArgumentException("Invalid region: $region"),
        };
        $ranges = array_values(array_filter(array_merge(...$rows)));

        if ($ranges === []) {
            return null;
        }

        $darkest = min(array_column($ranges, 0));
        $lightest = max(array_column($ranges, 1));
        $white = 1.05 / ($lightest + 0.05);
        $black = ($darkest + 0.05) / 0.05;

        return $white >= $black
            ? ['color' => '#ffffff', 'contrast' => round($white, 2)]
            : ['color' => '#000000', 'contrast' => round($black, 2)];
    }

    /**
     * Averages colors’ red, green, and blue channels in linear light, the same way the grid’s regions are. Alpha
     * channels are ignored.
     *
     * @param  list<string>  $colors
     */
    private function averageColor(array $colors): ?string
    {
        if ($colors === []) {
            return null;
        }

        $pixels = array_map(function (string $color): array {
            [$red, $green, $blue] = ColorGrid::parse($color);

            return [$red, $green, $blue, 1.0];
        }, $colors);

        return ColorGrid::average($pixels, count($pixels), 1, 1, 1)[0][0];
    }

    private static function isHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}([0-9a-f]{2})?$/i', $value) === 1;
    }

    /** @return array{dominant: string|null, grid: list<list<string>>, palette: list<string>, luminance: list<list<array{float, float}|null>>, weights: list<float>} */
    public function toArray(): array
    {
        return [
            'dominant' => $this->dominant,
            'grid' => $this->grid,
            'palette' => $this->palette,
            'luminance' => $this->luminance,
            'weights' => $this->weights,
        ];
    }

    /** @return array{dominant: string|null, grid: list<list<string>>, palette: list<string>, luminance: list<list<array{float, float}|null>>, weights: list<float>} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
