import {describe, expect, it} from 'vite-plus/test';
import {normalizeDate, readImageMetadata} from './image-metadata';

/** A 1×1 JPEG, as GD writes one. */
const JPEG = Uint8Array.from(
  atob(
    '/9j/4AAQSkZJRgABAQEAYABgAAD//gA+Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBkZWZhdWx0IHF1YWxpdHkK/9sAQwAIBgYHBgUIBwcHCQkICgwUDQwLCwwZEhMPFB0aHx4dGhwcICQuJyAiLCMcHCg3KSwwMTQ0NB8nOT04MjwuMzQy/9sAQwEJCQkMCwwYDQ0YMiEcITIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy/8AAEQgAAQABAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+f6KKKAP/9k='
  ),
  (character) => character.charCodeAt(0)
);

type Bytes = Uint8Array | string;

function bytes(...parts: Bytes[]): Uint8Array<ArrayBuffer> {
  const arrays = parts.map((part) =>
    typeof part === 'string'
      ? Uint8Array.from(part, (character) => character.charCodeAt(0))
      : part
  );
  const joined = new Uint8Array(arrays.reduce((sum, a) => sum + a.length, 0));
  let offset = 0;

  for (const array of arrays) {
    joined.set(array, offset);
    offset += array.length;
  }

  return joined;
}

const utf8 = (value: string) => new TextEncoder().encode(value);
const u16be = (value: number) =>
  bytes(String.fromCharCode(value >> 8, value & 0xff));
const u16le = (value: number) =>
  bytes(String.fromCharCode(value & 0xff, value >> 8));
const u32le = (value: number) =>
  bytes(
    String.fromCharCode(
      value & 0xff,
      (value >> 8) & 0xff,
      (value >> 16) & 0xff,
      value >>> 24
    )
  );
const u32be = (value: number) =>
  bytes(
    String.fromCharCode(
      value >>> 24,
      (value >> 16) & 0xff,
      (value >> 8) & 0xff,
      value & 0xff
    )
  );
const segment = (marker: number, payload: Uint8Array) =>
  bytes(String.fromCharCode(0xff, marker), u16be(payload.length + 2), payload);

/** The XMP properties wrapped in a packet, as the server's tests write them. */
function xmpPacket(properties: string, attributes = ''): Uint8Array {
  return utf8(`<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <rdf:Description
      xmlns:dc="http://purl.org/dc/elements/1.1/"
      xmlns:exif="http://ns.adobe.com/exif/1.0/"
      xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/"
      xmlns:Iptc4xmpCore="http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/"
      xmlns:Iptc4xmpExt="http://iptc.org/std/Iptc4xmpExt/2008-02-29/"
      ${attributes}>
      ${properties}
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>`);
}

/** IPTC records in a Photoshop APP13 segment, as PHP's `iptcembed()` writes them. */
function iptcSegment(records: Record<string, Bytes[]>): Uint8Array {
  const data = bytes(
    ...Object.entries(records).flatMap(([tag, values]) => {
      const [record, dataset] = tag.split('#').map(Number) as [number, number];

      return values.map((value) => {
        const valueBytes = bytes(value);

        return bytes(
          String.fromCharCode(0x1c, record, dataset),
          u16be(valueBytes.length),
          valueBytes
        );
      });
    })
  );

  return segment(
    0xed,
    bytes(
      'Photoshop 3.0\0',
      '8BIM',
      u16be(0x0404),
      '\0\0',
      u32be(data.length),
      data,
      data.length % 2 ? '\0' : ''
    )
  );
}

interface ExifTags {
  make?: string;
  model?: string;
  artist?: string;
  copyright?: string;
  dateTimeOriginal?: string;
  offsetTimeOriginal?: string;
  /** `true` gives just a latitude. */
  gps?: boolean | Gps;
}

interface Gps {
  latitude: [number, number, number];
  latitudeRef: string;
  longitude: [number, number, number];
  longitudeRef: string;
}

/** Degrees, minutes, and seconds as rationals, with seconds in hundredths. */
const rationals = ([degrees, minutes, seconds]: [number, number, number]) =>
  bytes(
    u32le(degrees),
    u32le(1),
    u32le(minutes),
    u32le(1),
    u32le(Math.round(seconds * 100)),
    u32le(100)
  );

type Entry = [tag: number, type: number, count: number, value: Uint8Array];

function ifd(entries: Entry[], offset: number): Uint8Array {
  const dataOffset = offset + 2 + 12 * entries.length + 4;
  const parts: Uint8Array[] = [u16le(entries.length)];
  const data: Uint8Array[] = [];
  let dataLength = 0;

  for (const [tag, type, count, value] of entries) {
    let field: Uint8Array;

    if (value.length <= 4) {
      field = bytes(value, '\0'.repeat(4 - value.length));
    } else {
      field = u32le(dataOffset + dataLength);
      const padded = value.length % 2 ? bytes(value, '\0') : value;
      data.push(padded);
      dataLength += padded.length;
    }

    parts.push(u16le(tag), u16le(type), u32le(count), field);
  }

  return bytes(...parts, u32le(0), ...data);
}

/** EXIF tags in a little-endian TIFF structure, as the server's tests write them. */
function exifSegment(exif: ExifTags): Uint8Array {
  const ascii = (tag: number, value: string): Entry => [
    tag,
    2,
    value.length + 1,
    bytes(value, '\0'),
  ];
  const ifd0 = [
    exif.make !== undefined ? ascii(0x010f, exif.make) : null,
    exif.model !== undefined ? ascii(0x0110, exif.model) : null,
    exif.artist !== undefined ? ascii(0x013b, exif.artist) : null,
    exif.copyright !== undefined ? ascii(0x8298, exif.copyright) : null,
  ].filter((entry): entry is Entry => entry !== null);
  const exifIfd = [
    exif.dateTimeOriginal !== undefined
      ? ascii(0x9003, exif.dateTimeOriginal)
      : null,
    exif.offsetTimeOriginal !== undefined
      ? ascii(0x9011, exif.offsetTimeOriginal)
      : null,
  ].filter((entry): entry is Entry => entry !== null);
  const gpsIfd: Entry[] =
    typeof exif.gps === 'object'
      ? [
          ascii(0x0001, exif.gps.latitudeRef),
          [0x0002, 5, 3, rationals(exif.gps.latitude)],
          ascii(0x0003, exif.gps.longitudeRef),
          [0x0004, 5, 3, rationals(exif.gps.longitude)],
        ]
      : exif.gps
        ? [
            ascii(0x0001, 'N'),
            [
              0x0002,
              5,
              3,
              bytes(
                u32le(51),
                u32le(1),
                u32le(30),
                u32le(1),
                u32le(0),
                u32le(1)
              ),
            ],
          ]
        : [];
  const pointers = (exifOffset: number, gpsOffset: number): Entry[] => [
    ...(exifIfd.length ? [[0x8769, 4, 1, u32le(exifOffset)] as Entry] : []),
    ...(gpsIfd.length ? [[0x8825, 4, 1, u32le(gpsOffset)] as Entry] : []),
  ];
  const exifOffset = 8 + ifd([...ifd0, ...pointers(0, 0)], 8).length;
  const gpsOffset =
    exifOffset + (exifIfd.length ? ifd(exifIfd, exifOffset).length : 0);

  return segment(
    0xe1,
    bytes(
      'Exif\0\0',
      'II*\0',
      u32le(8),
      ifd([...ifd0, ...pointers(exifOffset, gpsOffset)], 8),
      exifIfd.length ? ifd(exifIfd, exifOffset) : '',
      gpsIfd.length ? ifd(gpsIfd, gpsOffset) : ''
    )
  );
}

function jpeg({
  xmp,
  iptc,
  exif,
}: {
  xmp?: Uint8Array;
  iptc?: Record<string, Bytes[]>;
  exif?: ExifTags;
}): File {
  const segments = bytes(
    exif ? exifSegment(exif) : '',
    xmp ? segment(0xe1, bytes('http://ns.adobe.com/xap/1.0/\0', xmp)) : '',
    iptc ? iptcSegment(iptc) : ''
  );

  return new File(
    [bytes(JPEG.subarray(0, 2), segments, JPEG.subarray(2))],
    'photo.jpg',
    {
      type: 'image/jpeg',
    }
  );
}

const empty = {
  title: null,
  description: null,
  altText: null,
  creator: null,
  credit: null,
  copyright: null,
  keywords: [],
  dateTaken: null,
  camera: null,
  latitude: null,
  longitude: null,
  city: null,
  state: null,
  country: null,
};

describe('readImageMetadata', () => {
  it('reads XMP, preferring the default language', async () => {
    const file = jpeg({
      xmp: xmpPacket(
        `<dc:title><rdf:Alt><rdf:li xml:lang="fr">Le port</rdf:li><rdf:li xml:lang="x-default">The harbor</rdf:li></rdf:Alt></dc:title>
        <dc:description><rdf:Alt><rdf:li xml:lang="x-default">Boats at dusk</rdf:li></rdf:Alt></dc:description>
        <Iptc4xmpCore:AltTextAccessibility><rdf:Alt><rdf:li xml:lang="x-default">Fishing boats moored at dusk</rdf:li></rdf:Alt></Iptc4xmpCore:AltTextAccessibility>
        <dc:creator><rdf:Seq><rdf:li>Ada Lovelace</rdf:li><rdf:li>Grace Hopper</rdf:li></rdf:Seq></dc:creator>
        <dc:rights><rdf:Alt><rdf:li xml:lang="x-default">© 2026 Ada Lovelace</rdf:li></rdf:Alt></dc:rights>
        <dc:subject><rdf:Bag><rdf:li>harbor</rdf:li><rdf:li>boats</rdf:li><rdf:li>harbor</rdf:li></rdf:Bag></dc:subject>`,
        'photoshop:Credit="Lovelace Studio" photoshop:DateCreated="2026-05-01T14:03:22+02:00" exif:GPSLatitude="51,30.0N"'
      ),
    });

    expect(await readImageMetadata(file)).toEqual({
      ...empty,
      title: 'The harbor',
      description: 'Boats at dusk',
      altText: 'Fishing boats moored at dusk',
      creator: 'Ada Lovelace, Grace Hopper',
      credit: 'Lovelace Studio',
      copyright: '© 2026 Ada Lovelace',
      keywords: ['harbor', 'boats'],
      dateTaken: '2026-05-01T14:03:22+02:00',
    });
  });

  it('reads IPTC, decoding text that isn’t UTF-8 as Windows-1252', async () => {
    const file = jpeg({
      iptc: {
        '2#005': ['Harbor'],
        '2#120': ['Caf\xe9 by the harbor'],
        '2#080': ['Ada Lovelace', 'Grace Hopper'],
        '2#025': ['harbor', 'boats'],
        '2#110': ['Lovelace Studio'],
        '2#116': ['© 2026'.replace('©', '\xa9')],
        '2#055': ['20260501'],
        '2#060': ['140322+0200'],
      },
    });

    expect(await readImageMetadata(file)).toEqual({
      ...empty,
      title: 'Harbor',
      description: 'Café by the harbor',
      creator: 'Ada Lovelace, Grace Hopper',
      credit: 'Lovelace Studio',
      copyright: '© 2026',
      keywords: ['harbor', 'boats'],
      dateTaken: '2026-05-01T14:03:22+02:00',
    });
  });

  it('reads EXIF, including whether there’s a location', async () => {
    const file = jpeg({
      exif: {
        make: 'Canon',
        model: 'Canon EOS R5',
        artist: 'Ada Lovelace',
        copyright: 'Ada Lovelace',
        dateTimeOriginal: '2026:05:01 14:03:22',
        offsetTimeOriginal: '+02:00',
        gps: true,
      },
    });

    expect(await readImageMetadata(file)).toEqual({
      ...empty,
      creator: 'Ada Lovelace',
      copyright: 'Ada Lovelace',
      dateTaken: '2026-05-01T14:03:22+02:00',
      camera: 'Canon EOS R5',
    });
  });

  it('reads GPS coordinates from EXIF, south and west of zero as negative', async () => {
    const file = jpeg({
      exif: {
        gps: {
          latitude: [33, 51, 54.5],
          latitudeRef: 'S',
          longitude: [151, 12, 36],
          longitudeRef: 'E',
        },
      },
    });

    expect(await readImageMetadata(file)).toMatchObject({
      latitude: -33.865139,
      longitude: 151.21,
    });
  });

  it('reads GPS coordinates and place names from XMP, and place names from IPTC', async () => {
    const xmp = jpeg({
      xmp: xmpPacket(
        '<photoshop:City>Vienna</photoshop:City><photoshop:State>Vienna</photoshop:State><photoshop:Country>Austria</photoshop:Country>',
        'exif:GPSLatitude="48,12.5N" exif:GPSLongitude="16,22,12W"'
      ),
    });
    const iptc = jpeg({
      iptc: {
        '2#090': ['Sydney'],
        '2#095': ['New South Wales'],
        '2#101': ['Australia'],
      },
    });

    expect(await readImageMetadata(xmp)).toMatchObject({
      latitude: 48.208333,
      longitude: -16.37,
      city: 'Vienna',
      state: 'Vienna',
      country: 'Austria',
    });
    expect(await readImageMetadata(iptc)).toMatchObject({
      latitude: null,
      longitude: null,
      city: 'Sydney',
      state: 'New South Wales',
      country: 'Australia',
    });
  });

  it('prefers XMP, then IPTC, then EXIF, and names cameras by their make and model', async () => {
    const file = jpeg({
      xmp: xmpPacket(
        '<dc:title><rdf:Alt><rdf:li xml:lang="x-default">From XMP</rdf:li></rdf:Alt></dc:title>'
      ),
      iptc: {'2#005': ['From IPTC'], '2#116': ['IPTC rights']},
      exif: {make: 'NIKON CORPORATION', model: 'Z 6', copyright: 'EXIF rights'},
    });

    expect(await readImageMetadata(file)).toMatchObject({
      title: 'From XMP',
      copyright: 'IPTC rights',
      camera: 'NIKON CORPORATION Z 6',
    });
  });

  it('finds XMP in other image formats', async () => {
    const file = new File(
      [
        bytes(
          '\x89PNG\r\n\x1a\n',
          'iTXtXML:com.adobe.xmp\0\0\0\0\0',
          xmpPacket('<dc:title>Plain title</dc:title>')
        ),
      ],
      'logo.png',
      {type: 'image/png'}
    );

    expect(await readImageMetadata(file)).toEqual({
      ...empty,
      title: 'Plain title',
    });
  });

  it('leaves files it can’t read the way the server does to the server', async () => {
    expect(
      await readImageMetadata(new File(['%PDF-1.7'], 'doc.pdf'))
    ).toBeNull();
    expect(
      await readImageMetadata(new File(['II*\0'], 'scan.tiff'))
    ).toBeNull();
  });
});

describe('normalizeDate', () => {
  it('normalizes EXIF, XMP, and IPTC dates to ISO 8601', () => {
    expect(normalizeDate('2026:05:01 14:03:22')).toBe('2026-05-01T14:03:22');
    expect(normalizeDate('2026-05')).toBe('2026-05-01T00:00:00');
    expect(normalizeDate('20260501T140322+0200')).toBe(
      '2026-05-01T14:03:22+02:00'
    );
    expect(normalizeDate('2026-05-01T14:03:22.25Z')).toBe(
      '2026-05-01T14:03:22Z'
    );
    expect(normalizeDate('2026:02:30 00:00:00')).toBeNull();
    expect(normalizeDate('0000:00:00 00:00:00')).toBeNull();
    expect(normalizeDate('yesterday')).toBeNull();
  });
});
