<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Data\ImageMetadata;
use CraftCms\Cms\Image\ImageMetadataReader;

afterEach(function () {
    deleteMetadataJpegs();
});

it('reads XMP metadata', function () {
    $path = metadataJpeg(xmpPacket(<<<'XML'
<dc:title><rdf:Alt><rdf:li xml:lang="fr">Le port</rdf:li><rdf:li xml:lang="x-default">The harbor</rdf:li></rdf:Alt></dc:title>
<dc:description><rdf:Alt><rdf:li xml:lang="x-default">Boats at dusk</rdf:li></rdf:Alt></dc:description>
<Iptc4xmpCore:AltTextAccessibility><rdf:Alt><rdf:li xml:lang="x-default">Fishing boats moored at a pier</rdf:li></rdf:Alt></Iptc4xmpCore:AltTextAccessibility>
<dc:creator><rdf:Seq><rdf:li>Ada Park</rdf:li><rdf:li>Lee Moss</rdf:li></rdf:Seq></dc:creator>
<dc:rights><rdf:Alt><rdf:li xml:lang="x-default">© 2026 Ada Park</rdf:li></rdf:Alt></dc:rights>
<dc:subject><rdf:Bag><rdf:li>harbor</rdf:li><rdf:li>boats</rdf:li></rdf:Bag></dc:subject>
XML, 'photoshop:Credit="Ada Park / Harbor Press" photoshop:DateCreated="2026-05-01T19:42:10+02:00" exif:GPSLatitude="51,30.0N"'));

    expect(new ImageMetadataReader()->read($path))->toEqual(new ImageMetadata(
        title: 'The harbor',
        description: 'Boats at dusk',
        altText: 'Fishing boats moored at a pier',
        creator: 'Ada Park, Lee Moss',
        credit: 'Ada Park / Harbor Press',
        copyright: '© 2026 Ada Park',
        keywords: ['harbor', 'boats'],
        dateTaken: '2026-05-01T19:42:10+02:00',
    ));
});

it('reads alt text from either IPTC namespace', function (string $namespace) {
    $path = metadataJpeg(xmpPacket("<$namespace:AltTextAccessibility><rdf:Alt><rdf:li xml:lang=\"x-default\">A pier</rdf:li></rdf:Alt></$namespace:AltTextAccessibility>"));

    expect(new ImageMetadataReader()->read($path)->altText)->toBe('A pier');
})->with(['Iptc4xmpCore', 'Iptc4xmpExt']);

it('reads alt text written in other languages', function () {
    $path = metadataJpeg(xmpPacket('<Iptc4xmpCore:AltTextAccessibility><rdf:Alt><rdf:li xml:lang="x-default">A pier</rdf:li><rdf:li xml:lang="de">Ein Steg</rdf:li><rdf:li xml:lang="fr-CA">Un quai</rdf:li></rdf:Alt></Iptc4xmpCore:AltTextAccessibility>'));
    $metadata = new ImageMetadataReader()->read($path);

    expect($metadata->altText)->toBe('A pier')
        ->and($metadata->altTextTranslations)->toBe(['de' => 'Ein Steg', 'fr-ca' => 'Un quai'])
        ->and($metadata->altTextIn('de-CH'))->toBe('Ein Steg')
        ->and($metadata->altTextIn('fr-CA'))->toBe('Un quai')
        ->and($metadata->altTextIn('fr'))->toBeNull()
        ->and($metadata->altTextIn('en'))->toBeNull()
        ->and(ImageMetadata::fromArray($metadata->toArray()))->toEqual($metadata);
});

it('reads XMP packets that end past the first 8 KB of the file', function () {
    // Editing software often writes long packets, such as IPTC’s reference images, whose packets run to about 30 KB
    $padding = '<!-- '.str_repeat('padding ', 2000).'-->';
    $path = metadataJpeg(xmpPacket($padding.'<Iptc4xmpCore:AltTextAccessibility><rdf:Alt><rdf:li xml:lang="x-default">A pier</rdf:li></rdf:Alt></Iptc4xmpCore:AltTextAccessibility>'));

    expect(new ImageMetadataReader()->read($path)->altText)->toBe('A pier');
});

it('reads IPTC metadata', function () {
    $path = metadataJpeg(iptc: [
        '2#005' => 'The harbor',
        '2#120' => 'Boats at dusk',
        '2#080' => ['Ada Park', 'Lee Moss'],
        '2#110' => 'Harbor Press',
        '2#116' => 'Copyright 2026 Ada Park',
        '2#025' => ['harbor', 'boats'],
        '2#055' => '20260501',
        '2#060' => '194210+0200',
    ]);

    expect(new ImageMetadataReader()->read($path))->toEqual(new ImageMetadata(
        title: 'The harbor',
        description: 'Boats at dusk',
        creator: 'Ada Park, Lee Moss',
        credit: 'Harbor Press',
        copyright: 'Copyright 2026 Ada Park',
        keywords: ['harbor', 'boats'],
        dateTaken: '2026-05-01T19:42:10+02:00',
    ));
});

it('reads EXIF metadata', function () {
    $path = metadataJpeg(exif: [
        'make' => 'Canon',
        'model' => 'Canon EOS R5',
        'artist' => 'Ada Park',
        'copyright' => 'Copyright 2026 Ada Park',
        'dateTimeOriginal' => '2026:05:01 19:42:10',
        'offsetTimeOriginal' => '+02:00',
        'gps' => true,
    ]);

    expect(new ImageMetadataReader()->read($path))->toEqual(new ImageMetadata(
        creator: 'Ada Park',
        copyright: 'Copyright 2026 Ada Park',
        dateTaken: '2026-05-01T19:42:10+02:00',
        camera: 'Canon EOS R5',
    ));
})->skip(! function_exists('exif_read_data'), 'Needs the exif extension.');

it('reads GPS coordinates from EXIF, south and west of zero as negative', function () {
    $path = metadataJpeg(exif: ['gps' => [
        'latitude' => [33, 51, 54.5],
        'latitudeRef' => 'S',
        'longitude' => [151, 12, 36],
        'longitudeRef' => 'E',
    ]]);
    $metadata = new ImageMetadataReader()->read($path);

    expect([$metadata->latitude, $metadata->longitude])->toBe([-33.865139, 151.21]);
})->skip(! function_exists('exif_read_data'), 'Needs the exif extension.');

it('reads GPS coordinates and place names from XMP, and place names from IPTC', function () {
    $xmp = new ImageMetadataReader()->read(metadataJpeg(xmpPacket(
        '<photoshop:City>Vienna</photoshop:City><photoshop:State>Vienna</photoshop:State><photoshop:Country>Austria</photoshop:Country>',
        'exif:GPSLatitude="48,12.5N" exif:GPSLongitude="16,22,12W"',
    )));
    $iptc = new ImageMetadataReader()->read(metadataJpeg(iptc: ['2#090' => 'Sydney', '2#095' => 'New South Wales', '2#101' => 'Australia']));

    expect([$xmp->latitude, $xmp->longitude, $xmp->city, $xmp->state, $xmp->country])->toBe([48.208333, -16.37, 'Vienna', 'Vienna', 'Austria'])
        ->and([$iptc->city, $iptc->state, $iptc->country])->toBe(['Sydney', 'New South Wales', 'Australia'])
        ->and([$iptc->latitude, $iptc->longitude])->toBe([null, null]);
});

it('only keeps coordinates that are both there and in range', function () {
    expect(ImageMetadata::fromArray(['latitude' => 48.2, 'longitude' => 16.37])->latitude)->toBe(48.2)
        ->and(ImageMetadata::fromArray(['latitude' => 48.2])->latitude)->toBeNull()
        ->and(ImageMetadata::fromArray(['latitude' => 91, 'longitude' => 16.37])->latitude)->toBeNull()
        ->and(ImageMetadata::fromArray(['latitude' => '48.2', 'longitude' => 16.37])->latitude)->toBeNull();
});

it('prefers XMP to IPTC, and IPTC to EXIF', function () {
    $path = metadataJpeg(
        xmpPacket('<dc:creator><rdf:Seq><rdf:li>From XMP</rdf:li></rdf:Seq></dc:creator>'),
        iptc: ['2#080' => 'From IPTC', '2#116' => 'From IPTC'],
        exif: ['artist' => 'From EXIF', 'copyright' => 'From EXIF', 'dateTimeOriginal' => '2026:05:01 19:42:10'],
    );
    $metadata = new ImageMetadataReader()->read($path);

    expect($metadata->creator)->toBe('From XMP')
        ->and($metadata->copyright)->toBe('From IPTC')
        ->and($metadata->dateTaken)->toBe('2026-05-01T19:42:10');
})->skip(! function_exists('exif_read_data'), 'Needs the exif extension.');

it('names cameras by their make and model, without repeating the make', function (string $make, string $model, string $camera) {
    $path = metadataJpeg(exif: ['make' => $make, 'model' => $model]);

    expect(new ImageMetadataReader()->read($path)->camera)->toBe($camera);
})->with([
    'model with make' => ['Canon', 'Canon EOS R5', 'Canon EOS R5'],
    'model with part of make' => ['NIKON CORPORATION', 'NIKON Z 8', 'NIKON Z 8'],
    'model without make' => ['FUJIFILM', 'X-T5', 'FUJIFILM X-T5'],
])->skip(! function_exists('exif_read_data'), 'Needs the exif extension.');

it('converts IPTC text to UTF-8', function () {
    $path = metadataJpeg(iptc: ['2#120' => mb_convert_encoding('Café terrace', 'ISO-8859-1', 'UTF-8')]);

    expect(new ImageMetadataReader()->read($path)->description)->toBe('Café terrace');
});

it('ignores blank values', function () {
    $path = metadataJpeg(
        xmpPacket("<Iptc4xmpCore:AltTextAccessibility><rdf:Alt><rdf:li xml:lang=\"x-default\"> \n\t </rdf:li></rdf:Alt></Iptc4xmpCore:AltTextAccessibility>"),
        iptc: ['2#120' => " \n\t "],
    );

    expect(new ImageMetadataReader()->read($path))->toEqual(new ImageMetadata);
});

it('finds nothing in files without metadata, or that aren’t images', function () {
    $text = tempnam(sys_get_temp_dir(), 'craft-metadata-');
    file_put_contents($text, 'not an image');

    try {
        expect(new ImageMetadataReader()->read(metadataJpeg()))->toEqual(new ImageMetadata)
            ->and(new ImageMetadataReader()->read($text))->toEqual(new ImageMetadata);
    } finally {
        unlink($text);
    }
});

it('normalizes dates', function (string $value, ?string $date) {
    expect(new ImageMetadataReader()->date($value))->toBe($date);
})->with([
    'EXIF' => ['2026:05:01 19:42:10', '2026-05-01T19:42:10'],
    'XMP with an offset' => ['2026-05-01T19:42:10.25-05:00', '2026-05-01T19:42:10-05:00'],
    'XMP in UTC' => ['2026-05-01T19:42:10Z', '2026-05-01T19:42:10Z'],
    'XMP date only' => ['2026-05-01', '2026-05-01T00:00:00'],
    'IPTC' => ['20260501T194210+0200', '2026-05-01T19:42:10+02:00'],
    'blank EXIF date' => ['0000:00:00 00:00:00', null],
    'not a date' => ['2026-13-45', null],
    'not a date at all' => ['yesterday', null],
]);
