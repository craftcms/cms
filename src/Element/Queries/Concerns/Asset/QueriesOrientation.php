<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Asset;

use BackedEnum;
use CraftCms\Cms\Asset\Enums\ImageOrientation;
use CraftCms\Cms\Element\Queries\AssetQuery;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
trait QueriesOrientation
{
    /**
     * @var mixed The orientation(s) that the resulting images must have, based on their width and height.
     *
     * @see ImageOrientation
     *
     * @used-by orientation()
     */
    public mixed $orientation = null;

    protected function initQueriesOrientation(): void
    {
        $this->beforeQuery(static function (AssetQuery $assetQuery) {
            static::applyOrientation($assetQuery, $assetQuery->orientation);
        });
    }

    public static function applyOrientation(BuilderContract $query, mixed $value): void
    {
        if ($value === null || $value === []) {
            return;
        }

        $values = array_map(
            static fn (mixed $item): string => $item instanceof BackedEnum ? (string) $item->value : strtolower((string) $item),
            is_array($value) ? $value : [$value],
        );

        // Excluding orientations is the same as allowing the others
        if (($values[0] ?? null) === 'not') {
            $excluded = array_slice($values, 1);
            $values = array_values(array_filter(
                array_column(ImageOrientation::cases(), 'value'),
                static fn (string $orientation): bool => ! in_array($orientation, $excluded, true),
            ));
        }

        $orientations = array_values(array_filter(array_map(ImageOrientation::tryFrom(...), $values)));

        if ($orientations === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $grammar = DB::getQueryGrammar();
        $width = $grammar->wrap('assets.width');
        $height = $grammar->wrap('assets.height');
        $ratio = ImageOrientation::SQUARE_RATIO;
        // The larger side minus the smaller one, since MySQL’s unsigned columns can’t go negative
        $square = "(CASE WHEN $width > $height THEN $width - $height ELSE $height - $width END) * $ratio <= (CASE WHEN $width > $height THEN $width ELSE $height END)";

        $query->where(function (Builder $query) use ($orientations, $width, $height, $square) {
            $query->where('assets.width', '>', 0)->where('assets.height', '>', 0);
            $query->where(function (Builder $query) use ($orientations, $width, $height, $square) {
                foreach ($orientations as $orientation) {
                    $query->orWhereRaw(match ($orientation) {
                        ImageOrientation::Square => $square,
                        ImageOrientation::Landscape => "$width > $height AND NOT ($square)",
                        ImageOrientation::Portrait => "$height > $width AND NOT ($square)",
                    });
                }
            });
        });
    }

    /**
     * Narrows the query results based on whether the images are landscape, portrait, or square, by their width and
     * height. Images whose width and height differ by up to 2% count as square.
     *
     * Possible values include:
     *
     * | Value | Fetches images…
     * | - | -
     * | `'landscape'` | that are wider than they are tall.
     * | `['portrait', 'square']` | that are portrait or square.
     * | `['not', 'square']` | that aren’t square.
     *
     * @param  string|ImageOrientation|array<string|ImageOrientation>|null  $value
     *
     * @uses $orientation
     */
    public function orientation(mixed $value): static
    {
        $this->orientation = $value;

        return $this;
    }
}
