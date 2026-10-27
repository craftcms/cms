<?php

declare(strict_types=1);

/**
 * Writes a 1×1 JPEG to a temporary file, with any XMP packet, IPTC tags, and EXIF tags given, and returns its path.
 * Call {@see deleteMetadataJpegs()} after each test to delete them.
 *
 * @param  array<string, string|list<string>>  $iptc  IPTC tags, such as `'2#120' => 'Caption'`
 * @param  array{make?: string, model?: string, artist?: string, copyright?: string, dateTimeOriginal?: string, offsetTimeOriginal?: string, gps?: bool}  $exif
 */
function metadataJpeg(?string $xmp = null, array $iptc = [], array $exif = []): string
{
    $image = imagecreatetruecolor(1, 1);
    ob_start();
    imagejpeg($image);
    $jpeg = (string) ob_get_clean();
    $segments = '';

    if ($exif !== []) {
        $payload = "Exif\0\0".exifTiff($exif);
        $segments .= "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
    }

    if ($xmp !== null) {
        $payload = "http://ns.adobe.com/xap/1.0/\0".$xmp;
        $segments .= "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
    }

    $path = tempnam(sys_get_temp_dir(), 'craft-metadata-').'.jpg';
    file_put_contents($path, substr($jpeg, 0, 2).$segments.substr($jpeg, 2));

    if ($iptc !== []) {
        $data = '';

        foreach ($iptc as $tag => $values) {
            [$record, $dataset] = explode('#', $tag);

            foreach ((array) $values as $value) {
                $data .= "\x1C".chr((int) $record).chr((int) $dataset).pack('n', strlen($value)).$value;
            }
        }

        file_put_contents($path, iptcembed($data, $path));
    }

    $GLOBALS['metadataJpegs'][] = $path;

    return $path;
}

/**
 * Wraps XMP properties, written as RDF/XML, in an XMP packet.
 */
function xmpPacket(string $properties, string $attributes = ''): string
{
    return <<<XML
<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <rdf:Description
      xmlns:dc="http://purl.org/dc/elements/1.1/"
      xmlns:exif="http://ns.adobe.com/exif/1.0/"
      xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/"
      xmlns:Iptc4xmpCore="http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/"
      xmlns:Iptc4xmpExt="http://iptc.org/std/Iptc4xmpExt/2008-02-29/"
      $attributes>
      $properties
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>
XML;
}

/**
 * Builds the little-endian TIFF structure EXIF data is stored in.
 *
 * @param  array{make?: string, model?: string, artist?: string, copyright?: string, dateTimeOriginal?: string, offsetTimeOriginal?: string, gps?: bool|array{latitude: array{int, int, float}, latitudeRef: string, longitude: array{int, int, float}, longitudeRef: string}}  $exif
 *                                                                                                                                                                                                                                                                                     `gps` as `true` gives just a latitude
 */
function exifTiff(array $exif): string
{
    $ascii = fn (int $tag, string $value): array => [$tag, 2, strlen($value) + 1, $value."\0"];
    $ifd0 = array_values(array_filter([
        isset($exif['make']) ? $ascii(0x010F, $exif['make']) : null,
        isset($exif['model']) ? $ascii(0x0110, $exif['model']) : null,
        isset($exif['artist']) ? $ascii(0x013B, $exif['artist']) : null,
        isset($exif['copyright']) ? $ascii(0x8298, $exif['copyright']) : null,
    ]));
    $exifIfd = array_values(array_filter([
        isset($exif['dateTimeOriginal']) ? $ascii(0x9003, $exif['dateTimeOriginal']) : null,
        isset($exif['offsetTimeOriginal']) ? $ascii(0x9011, $exif['offsetTimeOriginal']) : null,
    ]));
    // Degrees, minutes, and seconds as rationals, with seconds in hundredths
    $rationals = fn (array $dms): string => pack('V6', $dms[0], 1, $dms[1], 1, (int) round($dms[2] * 100), 100);
    $gps = $exif['gps'] ?? false;
    $gpsIfd = match (true) {
        is_array($gps) => [
            $ascii(0x0001, $gps['latitudeRef']),
            [0x0002, 5, 3, $rationals($gps['latitude'])],
            $ascii(0x0003, $gps['longitudeRef']),
            [0x0004, 5, 3, $rationals($gps['longitude'])],
        ],
        $gps => [
            $ascii(0x0001, 'N'),
            [0x0002, 5, 3, pack('V6', 51, 1, 30, 1, 0, 1)],
        ],
        default => [],
    };

    // The sub-IFDs’ pointers are four bytes either way, so IFD0’s size doesn’t depend on where they point
    $pointers = fn (int $exifOffset, int $gpsOffset): array => array_merge(
        $exifIfd !== [] ? [[0x8769, 4, 1, pack('V', $exifOffset)]] : [],
        $gpsIfd !== [] ? [[0x8825, 4, 1, pack('V', $gpsOffset)]] : [],
    );
    $exifOffset = 8 + strlen(exifIfd([...$ifd0, ...$pointers(0, 0)], 8));
    $gpsOffset = $exifOffset + ($exifIfd !== [] ? strlen(exifIfd($exifIfd, $exifOffset)) : 0);

    return "II*\0".pack('V', 8)
        .exifIfd([...$ifd0, ...$pointers($exifOffset, $gpsOffset)], 8)
        .($exifIfd !== [] ? exifIfd($exifIfd, $exifOffset) : '')
        .($gpsIfd !== [] ? exifIfd($gpsIfd, $gpsOffset) : '');
}

/**
 * @param  list<array{int, int, int, string}>  $entries  Tag, type, count, and value bytes
 */
function exifIfd(array $entries, int $offset): string
{
    $dataOffset = $offset + 2 + 12 * count($entries) + 4;
    $directory = pack('v', count($entries));
    $data = '';

    foreach ($entries as [$tag, $type, $count, $bytes]) {
        if (strlen($bytes) <= 4) {
            $value = str_pad($bytes, 4, "\0");
        } else {
            $value = pack('V', $dataOffset + strlen($data));
            $data .= $bytes.(strlen($bytes) % 2 ? "\0" : '');
        }

        $directory .= pack('vvV', $tag, $type, $count).$value;
    }

    return $directory.pack('V', 0).$data;
}

function deleteMetadataJpegs(): void
{
    foreach ($GLOBALS['metadataJpegs'] ?? [] as $path) {
        @unlink($path);
    }

    $GLOBALS['metadataJpegs'] = [];
}
