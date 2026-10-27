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
     * @param  float|null  $latitude  The latitude of the GPS coordinates the file had, in decimal degrees (XMP
     *                                `exif:GPSLatitude`, EXIF GPSLatitude), read before it was cleaned. Not available to
     *                                sandboxed templates or GraphQL, since it says where the image was taken.
     * @param  float|null  $longitude  The longitude of the GPS coordinates the file had, in decimal degrees (XMP
     *                                 `exif:GPSLongitude`, EXIF GPSLongitude), read before it was cleaned. Not
     *                                 available to sandboxed templates or GraphQL, since it says where the image was
     *                                 taken.
     * @param  string|null  $city  The city the image shows or was taken in (XMP `photoshop:City`, IPTC City)
     * @param  string|null  $state  The state or province (XMP `photoshop:State`, IPTC Province/State)
     * @param  string|null  $country  The country (XMP `photoshop:Country`, IPTC Country)
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
        public ?float $latitude = null,
        public ?float $longitude = null,
        #[AllowedInSandbox]
        public ?string $city = null,
        #[AllowedInSandbox]
        public ?string $state = null,
        #[AllowedInSandbox]
        public ?string $country = null,
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
        $latitude = self::coordinate($data['latitude'] ?? null, 90);
        $longitude = self::coordinate($data['longitude'] ?? null, 180);
        // Only kept together, since one without the other isn’t a location
        [$latitude, $longitude] = $latitude !== null && $longitude !== null ? [$latitude, $longitude] : [null, null];

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
            latitude: $latitude,
            longitude: $longitude,
            city: self::text($data['city'] ?? null),
            state: self::text($data['state'] ?? null),
            country: self::text($data['country'] ?? null),
        );
    }

    /**
     * Returns a coordinate rounded to six decimal places, about 10 cm, or `null` if it isn’t a number within the limit.
     */
    private static function coordinate(mixed $value, int $limit): ?float
    {
        if (! is_int($value) && ! is_float($value)) {
            return null;
        }

        return abs($value) <= $limit ? round((float) $value, 6) : null;
    }

    /**
     * Returns the alt text the image’s embedded metadata suggests: its alt text, or else its description, which is
     * often written to describe the image too.
     */
    #[AllowedInSandbox]
    public function suggestedAlt(): ?string
    {
        return $this->altText ?? $this->description;
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

    /** @return array{title: string|null, description: string|null, altText: string|null, creator: string|null, credit: string|null, copyright: string|null, keywords: list<string>, dateTaken: string|null, camera: string|null, latitude: float|null, longitude: float|null, city: string|null, state: string|null, country: string|null} */
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
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
        ];
    }

    /** @return array{title: string|null, description: string|null, altText: string|null, creator: string|null, credit: string|null, copyright: string|null, keywords: list<string>, dateTaken: string|null, camera: string|null, latitude: float|null, longitude: float|null, city: string|null, state: string|null, country: string|null} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
