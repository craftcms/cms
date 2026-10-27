<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Data;

use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Metadata embedded in an image file by the camera or software that made it, from its XMP, IPTC, and EXIF data.
 *
 * Where a value is in more than one of them, XMP’s is used first, then IPTC’s, then EXIF’s, since editing software
 * usually updates XMP.
 *
 * @see Images::metadata()
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
#[AllowedInSandbox]
readonly class ImageMetadata implements Arrayable, JsonSerializable
{
    /**
     * The most characters kept of each value, so a file can’t store more than a short paragraph in one.
     */
    public const int MAX_LENGTH = 1000;

    /**
     * The most keywords kept.
     */
    public const int MAX_KEYWORDS = 50;

    /**
     * @param  string|null  $title  A short title for the image (XMP `dc:title`, IPTC Object Name)
     * @param  string|null  $description  A description or caption of the image (XMP `dc:description`, IPTC
     *                                    Caption/Abstract)
     * @param  string|null  $altText  Alt text written for the image (IPTC Alt Text (Accessibility))
     * @param  string|null  $creator  Who made the image, such as the photographer (XMP `dc:creator`, IPTC By-line,
     *                                EXIF Artist)
     * @param  string|null  $credit  How the image should be credited when it’s published (XMP `photoshop:Credit`,
     *                               IPTC Credit Line)
     * @param  string|null  $copyright  The image’s copyright notice (XMP `dc:rights`, IPTC Copyright Notice, EXIF
     *                                  Copyright)
     * @param  list<string>  $keywords  Keywords the image was tagged with (XMP `dc:subject`, IPTC Keywords)
     * @param  string|null  $dateTaken  When the image was taken or made, as an ISO 8601 date and time, with a UTC
     *                                  offset if the file has one (e.g. `2026-05-01T14:03:22+02:00`)
     * @param  string|null  $camera  The camera’s make and model (EXIF Make and Model)
     * @param  bool  $hasLocation  Whether the file has GPS coordinates in it, such as where a photo was taken. The
     *                             coordinates themselves aren’t kept.
     */
    public function __construct(
        #[AllowedInSandbox]
        public ?string $title = null,
        #[AllowedInSandbox]
        public ?string $description = null,
        #[AllowedInSandbox]
        public ?string $altText = null,
        #[AllowedInSandbox]
        public ?string $creator = null,
        #[AllowedInSandbox]
        public ?string $credit = null,
        #[AllowedInSandbox]
        public ?string $copyright = null,
        #[AllowedInSandbox]
        public array $keywords = [],
        #[AllowedInSandbox]
        public ?string $dateTaken = null,
        #[AllowedInSandbox]
        public ?string $camera = null,
        #[AllowedInSandbox]
        public bool $hasLocation = false,
    ) {}

    /**
     * Creates an instance from stored metadata.
     *
     * Anything that isn’t valid for its part is dropped.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keywords = is_array($data['keywords'] ?? null) ? $data['keywords'] : [];
        $dateTaken = $data['dateTaken'] ?? null;

        return new self(
            title: self::text($data['title'] ?? null),
            description: self::text($data['description'] ?? null),
            altText: self::text($data['altText'] ?? null),
            creator: self::text($data['creator'] ?? null),
            credit: self::text($data['credit'] ?? null),
            copyright: self::text($data['copyright'] ?? null),
            keywords: array_slice(array_values(array_filter(array_map(self::text(...), $keywords))), 0, self::MAX_KEYWORDS),
            dateTaken: is_string($dateTaken) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}([+-]\d{2}:\d{2}|Z)?$/', $dateTaken) ? $dateTaken : null,
            camera: self::text($data['camera'] ?? null),
            hasLocation: ($data['hasLocation'] ?? false) === true,
        );
    }

    /**
     * Returns a value trimmed and cut to {@see MAX_LENGTH} characters, or `null` if it’s blank or isn’t text.
     */
    public static function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $value = trim(mb_substr((string) $value, 0, self::MAX_LENGTH));

        return $value !== '' ? $value : null;
    }

    /**
     * Returns a copy that says whether the file has GPS coordinates in it, keeping everything else, such as after it’s
     * been cleaned of them.
     */
    public function withHasLocation(bool $hasLocation): self
    {
        return new self(
            $this->title,
            $this->description,
            $this->altText,
            $this->creator,
            $this->credit,
            $this->copyright,
            $this->keywords,
            $this->dateTaken,
            $this->camera,
            $hasLocation,
        );
    }

    /** @return array{title: string|null, description: string|null, altText: string|null, creator: string|null, credit: string|null, copyright: string|null, keywords: list<string>, dateTaken: string|null, camera: string|null, hasLocation: bool} */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'altText' => $this->altText,
            'creator' => $this->creator,
            'credit' => $this->credit,
            'copyright' => $this->copyright,
            'keywords' => $this->keywords,
            'dateTaken' => $this->dateTaken,
            'camera' => $this->camera,
            'hasLocation' => $this->hasLocation,
        ];
    }

    /** @return array{title: string|null, description: string|null, altText: string|null, creator: string|null, credit: string|null, copyright: string|null, keywords: list<string>, dateTaken: string|null, camera: string|null, hasLocation: bool} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
