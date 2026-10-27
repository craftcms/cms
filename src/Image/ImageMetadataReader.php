<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Image\Data\ImageMetadata;
use DOMDocument;
use DOMXPath;
use Imagick;
use Throwable;

/**
 * Reads the metadata embedded in image files, from their XMP, IPTC, and EXIF data.
 *
 * It only reads the file’s metadata, not its pixels, so it’s cheap enough to run on every upload, but it has to run
 * before uploads are cleaned, which strips metadata unless the `preserveExifData` config setting is enabled.
 *
 * @see Images::metadata()
 * @since 6.0.0
 */
class ImageMetadataReader
{
    /**
     * How much of the start of a file is searched for its XMP packet, which covers it in most images.
     */
    private const int XMP_SEARCH_LENGTH = 262144;

    private const array XMP_NAMESPACES = [
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'exif' => 'http://ns.adobe.com/exif/1.0/',
        'photoshop' => 'http://ns.adobe.com/photoshop/1.0/',
        'xmp' => 'http://ns.adobe.com/xap/1.0/',
        'Iptc4xmpCore' => 'http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/',
        'Iptc4xmpExt' => 'http://iptc.org/std/Iptc4xmpExt/2008-02-29/',
    ];

    public function __construct(
        private readonly bool $useImagick = false,
    ) {}

    /**
     * Returns the metadata embedded in an image file. Anything that can’t be read is left out.
     */
    public function read(string $filePath): ImageMetadata
    {
        $xmp = $this->xmp($filePath);
        $iptc = $this->iptc($filePath);
        $exif = $this->exif($filePath);

        $keywords = $xmp ? $this->xmpList($xmp, 'dc:subject') : [];

        if ($keywords === []) {
            $keywords = array_map($this->decode(...), (array) ($iptc['2#025'] ?? []));
        }

        $make = $this->decode($exif['Make'] ?? null);
        $model = $this->decode($exif['Model'] ?? null);

        return ImageMetadata::fromArray([
            'title' => $this->first($xmp ? $this->xmpText($xmp, 'dc:title') : null, $this->iptcText($iptc, '2#005')),
            // EXIF’s ImageDescription is left out, since cameras fill it with their names, like “OLYMPUS DIGITAL CAMERA”
            'description' => $this->first($xmp ? $this->xmpText($xmp, 'dc:description') : null, $this->iptcText($iptc, '2#120')),
            'altText' => $xmp ? $this->first(
                $this->xmpText($xmp, 'Iptc4xmpCore:AltTextAccessibility'),
                $this->xmpText($xmp, 'Iptc4xmpExt:AltTextAccessibility'),
            ) : null,
            'creator' => $this->first(
                $xmp ? implode(', ', $this->xmpList($xmp, 'dc:creator')) : null,
                implode(', ', array_filter(array_map($this->decode(...), (array) ($iptc['2#080'] ?? [])))),
                $this->decode($exif['Artist'] ?? null),
            ),
            'credit' => $this->first($xmp ? $this->xmpText($xmp, 'photoshop:Credit') : null, $this->iptcText($iptc, '2#110')),
            'copyright' => $this->first(
                $xmp ? $this->xmpText($xmp, 'dc:rights') : null,
                $this->iptcText($iptc, '2#116'),
                $this->decode($exif['Copyright'] ?? null),
            ),
            'keywords' => array_values(array_unique(array_filter($keywords))),
            'dateTaken' => $this->first(
                $xmp ? $this->date($this->first(
                    $this->xmpText($xmp, 'photoshop:DateCreated'),
                    $this->xmpText($xmp, 'exif:DateTimeOriginal'),
                )) : null,
                $this->date(
                    isset($iptc['2#055'][0]) ? $iptc['2#055'][0].(isset($iptc['2#060'][0]) ? 'T'.$iptc['2#060'][0] : '') : null,
                ),
                $this->date(
                    isset($exif['DateTimeOriginal']) && is_string($exif['DateTimeOriginal'])
                        ? $exif['DateTimeOriginal'].(is_string($exif['OffsetTimeOriginal'] ?? null) ? $exif['OffsetTimeOriginal'] : '')
                        : null,
                ),
            ),
            'camera' => match (true) {
                $model === null => $make,
                // Most cameras’ models start with their make already, such as “Canon EOS R5”
                $make === null || stripos($model, explode(' ', $make)[0]) !== false => $model,
                default => "$make $model",
            },
            'hasLocation' => isset($exif['GPSLatitude']) || ($xmp && $this->xmpText($xmp, 'exif:GPSLatitude') !== null),
        ]);
    }

    /**
     * Normalizes an EXIF, XMP, or IPTC date to an ISO 8601 date and time, or returns `null` if it isn’t one.
     */
    public function date(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $pattern = '/^(\d{4})[:-]?(\d{2})(?:[:-]?(\d{2})(?:[T ](\d{2}):?(\d{2})(?::?(\d{2})(?:\.\d+)?)?)?)?\s*(Z|[+-]\d{2}:?\d{2})?$/';

        if (! preg_match($pattern, trim($value), $match)) {
            return null;
        }

        [$year, $month, $day, $hour, $minute, $second] = [
            (int) $match[1],
            (int) $match[2],
            (int) (($match[3] ?? '') ?: 1),
            (int) ($match[4] ?? 0),
            (int) ($match[5] ?? 0),
            (int) ($match[6] ?? 0),
        ];

        if ($year === 0 || ! checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        $offset = $match[7] ?? '';

        if ($offset !== '' && $offset !== 'Z') {
            $offset = substr($offset, 0, 3).':'.substr($offset, -2);
        }

        return sprintf('%04d-%02d-%02dT%02d:%02d:%02d%s', $year, $month, $day, $hour, $minute, $second, $offset);
    }

    private function xmp(string $filePath): ?DOMXPath
    {
        $packet = null;

        try {
            $handle = fopen($filePath, 'rb');

            if ($handle !== false) {
                $chunk = (string) fread($handle, self::XMP_SEARCH_LENGTH);
                fclose($handle);
                $start = strpos($chunk, '<x:xmpmeta');
                $end = $start !== false ? strpos($chunk, '</x:xmpmeta>', $start) : false;

                if ($start !== false && $end !== false) {
                    $packet = substr($chunk, $start, $end - $start + strlen('</x:xmpmeta>'));
                }
            }

            // Imagick finds packets that are further into the file, such as at the end of WebP files
            if ($packet === null && $this->useImagick && class_exists(Imagick::class)) {
                $imagick = new Imagick;
                $imagick->pingImage($filePath);
                $packet = $imagick->getImageProfiles('xmp')['xmp'] ?? null;
                $imagick->clear();
            }

            if (! $packet) {
                return null;
            }

            $dom = new DOMDocument;

            if (! @$dom->loadXML($packet, LIBXML_NONET)) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        $xpath = new DOMXPath($dom);

        foreach (self::XMP_NAMESPACES as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }

        return $xpath;
    }

    /**
     * Returns an XMP property’s text, preferring the default language’s for properties that have several.
     */
    private function xmpText(DOMXPath $xpath, string $property): ?string
    {
        foreach ([
            "//$property/rdf:Alt/rdf:li[@xml:lang='x-default']",
            "//$property/rdf:Alt/rdf:li",
            "//{$property}[not(rdf:Alt)]",
            "//rdf:Description/@$property",
        ] as $query) {
            foreach ($xpath->query($query) ?: [] as $node) {
                if (($value = $this->decode($node->textContent)) !== null) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Returns the items of an XMP list property, such as its creators.
     *
     * @return list<string>
     */
    private function xmpList(DOMXPath $xpath, string $property): array
    {
        $values = [];

        foreach ($xpath->query("//$property/rdf:Seq/rdf:li | //$property/rdf:Bag/rdf:li") ?: [] as $node) {
            if (($value = $this->decode($node->textContent)) !== null) {
                $values[] = $value;
            }
        }

        if ($values === [] && ($value = $this->xmpText($xpath, $property)) !== null) {
            $values[] = $value;
        }

        return $values;
    }

    /** @return array<string, list<string>> */
    private function iptc(string $filePath): array
    {
        try {
            $info = [];
            @getimagesize($filePath, $info);

            return isset($info['APP13']) ? (iptcparse($info['APP13']) ?: []) : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, list<string>>  $iptc
     */
    private function iptcText(array $iptc, string $tag): ?string
    {
        return $this->decode(implode(' ', $iptc[$tag] ?? []));
    }

    /** @return array<string, mixed> */
    private function exif(string $filePath): array
    {
        if (! function_exists('exif_read_data')) {
            return [];
        }

        try {
            return @exif_read_data($filePath) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Converts a metadata value to UTF-8 text. Values that aren’t UTF-8 already are usually Windows-1252, which IPTC
     * and EXIF text often is when the file doesn’t say what it is.
     */
    private function decode(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = str_replace("\0", '', $value);

        return ImageMetadata::text(mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'Windows-1252'));
    }

    private function first(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
