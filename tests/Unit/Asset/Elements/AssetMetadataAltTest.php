<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;

beforeEach(function () {
    $GLOBALS['assetAltTempFiles'] = [];
});

afterEach(function () {
    foreach ($GLOBALS['assetAltTempFiles'] as $file) {
        @unlink($file);
    }
});

it('extracts alt text from xmp accessibility metadata', function () {
    $file = createAssetMetadataAltJpegWithXmp(altTextAccessibility: 'Accessible XMP alt');

    expect(invokeAssetMetadataAltMethod('_getAltFromXmpMetadata', $file))->toBe('Accessible XMP alt');
});

it('extracts alt text from xmp extended accessibility metadata', function () {
    $file = createAssetMetadataAltJpegWithXmp(altTextAccessibility: 'Extended XMP alt', namespace: 'Iptc4xmpExt');

    expect(invokeAssetMetadataAltMethod('_getAltFromXmpMetadata', $file))->toBe('Extended XMP alt');
});

it('falls back to xmp description metadata', function () {
    $file = createAssetMetadataAltJpegWithXmp(description: 'Description XMP alt');

    expect(invokeAssetMetadataAltMethod('_getAltFromXmpMetadata', $file))->toBe('Description XMP alt');
});

it('ignores blank xmp metadata values', function () {
    $file = createAssetMetadataAltJpegWithXmp(altTextAccessibility: " \n\t ");

    expect(invokeAssetMetadataAltMethod('_getAltFromXmpMetadata', $file))->toBeNull();
});

it('extracts alt text from iptc caption metadata', function () {
    $file = createAssetMetadataAltJpegWithIptcCaption('IPTC caption alt');

    expect(invokeAssetMetadataAltMethod('_getAltFromIptcMetadata', $file))->toBe('IPTC caption alt');
});

it('ignores blank iptc caption metadata', function () {
    $file = createAssetMetadataAltJpegWithIptcCaption(" \n\t ");

    expect(invokeAssetMetadataAltMethod('_getAltFromIptcMetadata', $file))->toBeNull();
});

function invokeAssetMetadataAltMethod(string $method, string $file): ?string
{
    return new ReflectionMethod(Asset::class, $method)->invoke(new Asset, $file);
}

function createAssetMetadataAltJpegWithXmp(
    ?string $altTextAccessibility = null,
    ?string $description = null,
    string $namespace = 'Iptc4xmpCore',
): string {
    $property = match ($namespace) {
        'Iptc4xmpExt' => 'Iptc4xmpExt:AltTextAccessibility',
        default => 'Iptc4xmpCore:AltTextAccessibility',
    };

    $metadata = $altTextAccessibility !== null
        ? sprintf(
            '<%1$s><rdf:Alt><rdf:li xml:lang="x-default">%2$s</rdf:li></rdf:Alt></%1$s>',
            $property,
            htmlspecialchars($altTextAccessibility, ENT_XML1),
        )
        : sprintf(
            '<dc:description><rdf:Alt><rdf:li xml:lang="x-default">%s</rdf:li></rdf:Alt></dc:description>',
            htmlspecialchars((string) $description, ENT_XML1),
        );

    $xmp = <<<XML
<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <rdf:Description
      xmlns:dc="http://purl.org/dc/elements/1.1/"
      xmlns:Iptc4xmpCore="http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/"
      xmlns:Iptc4xmpExt="http://iptc.org/std/Iptc4xmpExt/2008-02-29/">
      $metadata
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>
XML;

    $payload = "http://ns.adobe.com/xap/1.0/\0".$xmp;
    $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
    $jpeg = createAssetMetadataAltJpegBytes();

    return createAssetMetadataAltTempJpeg(substr($jpeg, 0, 2).$segment.substr($jpeg, 2));
}

function createAssetMetadataAltJpegWithIptcCaption(string $caption): string
{
    $file = createAssetMetadataAltTempJpeg(createAssetMetadataAltJpegBytes());
    $iptc = "\x1C\x02\x78".pack('n', strlen($caption)).$caption;
    $content = iptcembed($iptc, $file);

    expect($content)->not->toBeFalse();

    file_put_contents($file, $content);

    return $file;
}

function createAssetMetadataAltJpegBytes(): string
{
    $image = imagecreatetruecolor(1, 1);

    ob_start();
    imagejpeg($image);

    return (string) ob_get_clean();
}

function createAssetMetadataAltTempJpeg(string $contents): string
{
    $file = tempnam(sys_get_temp_dir(), 'craft-asset-alt-').'.jpg';
    file_put_contents($file, $contents);
    $GLOBALS['assetAltTempFiles'][] = $file;

    return $file;
}
