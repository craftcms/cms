<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Data;

use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * What was found in an image: its colors, sampled from the file, and its keywords and the text in it, found by AI.
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
#[AllowedInSandbox]
readonly class ImageAnalysis implements Arrayable, JsonSerializable
{
    /**
     * @param  ImageColors|null  $colors  Color data sampled from the image, or `null` if it hasn’t been sampled yet
     * @param  list<string>|null  $keywords  Words and short phrases that describe the image, such as what’s in it,
     *                                       written in the primary site’s language by the “Tag images” AI task, or
     *                                       `null` if it hasn’t been tagged
     * @param  string|null  $text  Text that appears in the image, as found by the “Tag images” AI task, or `null` if it
     *                             has none or hasn’t been tagged. Whether it’s been tagged is shown by `$keywords`.
     */
    public function __construct(
        #[AllowedInSandbox]
        public ?ImageColors $colors = null,
        #[AllowedInSandbox]
        public ?array $keywords = null,
        #[AllowedInSandbox]
        public ?string $text = null,
    ) {}

    /**
     * Creates an instance from stored analysis data.
     *
     * Anything that isn’t valid for its part, such as keywords that aren’t strings, is dropped.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $colors = $data['colors'] ?? null;
        $keywords = $data['keywords'] ?? null;
        $text = $data['text'] ?? null;

        return new self(
            colors: is_array($colors) ? ImageColors::fromArray($colors) : null,
            keywords: is_array($keywords)
                ? array_values(array_filter(array_map(
                    static fn (mixed $keyword): string => is_scalar($keyword) ? trim((string) $keyword) : '',
                    $keywords,
                ), static fn (string $keyword): bool => $keyword !== ''))
                : null,
            text: is_string($text) && trim($text) !== '' ? $text : null,
        );
    }

    /**
     * Returns a copy with new colors, keeping everything else.
     */
    public function withColors(?ImageColors $colors): self
    {
        return new self($colors, $this->keywords, $this->text);
    }

    /**
     * Returns a copy with new keywords and text, keeping everything else.
     *
     * @param  list<string>|null  $keywords
     */
    public function withTags(?array $keywords, ?string $text): self
    {
        return new self($this->colors, $keywords, $text);
    }

    /** @return array{colors: array{dominant: string|null, grid: list<list<string>>, palette: list<string>}|null, keywords: list<string>|null, text: string|null} */
    public function toArray(): array
    {
        return [
            'colors' => $this->colors?->toArray(),
            'keywords' => $this->keywords,
            'text' => $this->text,
        ];
    }

    /** @return array{colors: array{dominant: string|null, grid: list<list<string>>, palette: list<string>}|null, keywords: list<string>|null, text: string|null} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
