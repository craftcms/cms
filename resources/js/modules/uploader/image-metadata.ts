/**
 * Reads the metadata embedded in an image file in the browser, from its XMP,
 * IPTC, and EXIF data, the same way `CraftCms\Cms\Image\ImageMetadataReader`
 * does on the server, so uploads can send it along instead of the server
 * downloading the file to read it.
 */

/** Metadata embedded in an image file, as `CraftCms\Cms\Image\Data\ImageMetadata` holds it. */
export interface ImageMetadata {
  title: string | null;
  description: string | null;
  altText: string | null;
  creator: string | null;
  credit: string | null;
  copyright: string | null;
  keywords: string[];
  dateTaken: string | null;
  camera: string | null;
  latitude: number | null;
  longitude: number | null;
  city: string | null;
  state: string | null;
  country: string | null;
}

/** How much of the start of a file is searched for its XMP packet, which covers it in most images. */
const XMP_SEARCH_LENGTH = 262144;
/** The most characters kept of each value. */
const MAX_LENGTH = 1000;
const MAX_KEYWORDS = 50;
/** The characters PHP's `trim()` trims. */
const PHP_TRIM_CHARACTERS = ' \t\n\r\0\u000B';

const NAMESPACES: Record<string, string> = {
  rdf: 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
  dc: 'http://purl.org/dc/elements/1.1/',
  exif: 'http://ns.adobe.com/exif/1.0/',
  photoshop: 'http://ns.adobe.com/photoshop/1.0/',
  xmp: 'http://ns.adobe.com/xap/1.0/',
  Iptc4xmpCore: 'http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/',
  Iptc4xmpExt: 'http://iptc.org/std/Iptc4xmpExt/2008-02-29/',
};
const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

type Iptc = Record<string, Uint8Array[]>;
type Exif = Record<string, Uint8Array>;

/** EXIF tags, and the GPS coordinates in decimal degrees if the file has both. */
interface ExifData {
  tags: Exif;
  latitude: number | null;
  longitude: number | null;
}

/**
 * Reads an image file's metadata, or resolves to `null` if it isn't a JPEG,
 * PNG, GIF, or WebP file, whose metadata the server would read the same way,
 * leaving the server to read it instead.
 */
export async function readImageMetadata(
  file: Blob
): Promise<ImageMetadata | null> {
  try {
    const start = new Uint8Array(
      await file.slice(0, XMP_SEARCH_LENGTH).arrayBuffer()
    );
    const format = imageFormat(start);

    if (!format) {
      return null;
    }

    // IPTC and EXIF data is in the segments at the start of a JPEG
    const segments = format === 'jpeg' ? jpegSegments(start) : [];

    return parseImageMetadata(
      xmpDocument(start),
      iptc(segments),
      exif(segments)
    );
  } catch {
    return null;
  }
}

function imageFormat(bytes: Uint8Array): 'jpeg' | 'other' | null {
  const ascii = (offset: number, length: number) =>
    String.fromCharCode(...bytes.subarray(offset, offset + length));

  if (bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff) {
    return 'jpeg';
  }

  if (
    ascii(1, 3) === 'PNG' ||
    ascii(0, 4) === 'GIF8' ||
    (ascii(0, 4) === 'RIFF' && ascii(8, 4) === 'WEBP')
  ) {
    return 'other';
  }

  return null;
}

function parseImageMetadata(
  xmp: Document | null,
  iptcData: Iptc,
  {tags: exifData, latitude: exifLatitude, longitude: exifLongitude}: ExifData
): ImageMetadata {
  let keywords = xmp ? xmpList(xmp, 'dc:subject') : [];

  if (keywords.length === 0) {
    keywords = (iptcData['2#025'] ?? [])
      .map(decode)
      .filter((value): value is string => value !== null);
  }

  const latitude =
    (xmp ? xmpCoordinate(xmpText(xmp, 'exif:GPSLatitude')) : null) ??
    exifLatitude;
  const longitude =
    (xmp ? xmpCoordinate(xmpText(xmp, 'exif:GPSLongitude')) : null) ??
    exifLongitude;
  // Only kept together, since one without the other isn't a location
  const hasCoordinates =
    latitude !== null &&
    longitude !== null &&
    Math.abs(latitude) <= 90 &&
    Math.abs(longitude) <= 180;
  const make = decode(exifData.Make);
  const model = decode(exifData.Model);
  const iptcDate = iptcData['2#055']?.[0];
  const iptcTime = iptcData['2#060']?.[0];
  const exifDate = exifData.DateTimeOriginal;
  const exifOffset = exifData.OffsetTimeOriginal;

  return {
    title: first(xmp && xmpText(xmp, 'dc:title'), iptcText(iptcData, '2#005')),
    // EXIF's ImageDescription is left out, since cameras fill it with their
    // names, like “OLYMPUS DIGITAL CAMERA”
    description: first(
      xmp && xmpText(xmp, 'dc:description'),
      iptcText(iptcData, '2#120')
    ),
    altText: xmp
      ? first(
          xmpText(xmp, 'Iptc4xmpCore:AltTextAccessibility'),
          xmpText(xmp, 'Iptc4xmpExt:AltTextAccessibility')
        )
      : null,
    creator: first(
      xmp && xmpList(xmp, 'dc:creator').join(', '),
      (iptcData['2#080'] ?? [])
        .map(decode)
        .filter((value) => value !== null && value !== '0')
        .join(', '),
      decode(exifData.Artist)
    ),
    credit: first(
      xmp && xmpText(xmp, 'photoshop:Credit'),
      iptcText(iptcData, '2#110')
    ),
    copyright: first(
      xmp && xmpText(xmp, 'dc:rights'),
      iptcText(iptcData, '2#116'),
      decode(exifData.Copyright)
    ),
    keywords: [...new Set(keywords.filter((keyword) => keyword !== '0'))]
      .map(text)
      .filter((keyword): keyword is string => keyword !== null)
      .slice(0, MAX_KEYWORDS),
    dateTaken: first(
      xmp
        ? normalizeDate(
            first(
              xmpText(xmp, 'photoshop:DateCreated'),
              xmpText(xmp, 'exif:DateTimeOriginal')
            )
          )
        : null,
      normalizeDate(
        iptcDate
          ? latin1(iptcDate) + (iptcTime ? `T${latin1(iptcTime)}` : '')
          : null
      ),
      normalizeDate(
        exifDate
          ? latin1(withoutNul(exifDate)) +
              (exifOffset ? latin1(withoutNul(exifOffset)) : '')
          : null
      )
    ),
    camera:
      model === null
        ? make
        : // Most cameras' models start with their make already, such as “Canon EOS R5”
          make === null ||
            model.toLowerCase().includes(make.split(' ')[0]!.toLowerCase())
          ? model
          : `${make} ${model}`,
    latitude: hasCoordinates ? roundCoordinate(latitude) : null,
    longitude: hasCoordinates ? roundCoordinate(longitude) : null,
    city: first(
      xmp && xmpText(xmp, 'photoshop:City'),
      iptcText(iptcData, '2#090')
    ),
    state: first(
      xmp && xmpText(xmp, 'photoshop:State'),
      iptcText(iptcData, '2#095')
    ),
    country: first(
      xmp && xmpText(xmp, 'photoshop:Country'),
      iptcText(iptcData, '2#101')
    ),
  };
}

/**
 * Normalizes an EXIF, XMP, or IPTC date to an ISO 8601 date and time, or
 * returns `null` if it isn't one.
 */
export function normalizeDate(value: string | null): string | null {
  if (value === null) {
    return null;
  }

  const match = phpTrim(value).match(
    /^(\d{4})[:-]?(\d{2})(?:[:-]?(\d{2})(?:[T ](\d{2}):?(\d{2})(?::?(\d{2})(?:\.\d+)?)?)?)?\s*(Z|[+-]\d{2}:?\d{2})?$/
  );

  if (!match) {
    return null;
  }

  const [year, month, day, hour, minute, second] = [
    Number(match[1]),
    Number(match[2]),
    Number(match[3] || 1),
    Number(match[4] ?? 0),
    Number(match[5] ?? 0),
    Number(match[6] ?? 0),
  ];
  const validDay =
    month >= 1 &&
    month <= 12 &&
    day >= 1 &&
    day <= new Date(Date.UTC(year, month, 0)).getUTCDate();

  if (year === 0 || !validDay || hour > 23 || minute > 59 || second > 59) {
    return null;
  }

  let offset = match[7] ?? '';

  if (offset !== '' && offset !== 'Z') {
    offset = `${offset.slice(0, 3)}:${offset.slice(-2)}`;
  }

  const pad = (number: number, length = 2) =>
    String(number).padStart(length, '0');

  return `${pad(year, 4)}-${pad(month)}-${pad(day)}T${pad(hour)}:${pad(minute)}:${pad(second)}${offset}`;
}

function xmpDocument(bytes: Uint8Array): Document | null {
  const start = indexOf(bytes, '<x:xmpmeta');
  const close = '</x:xmpmeta>';
  const end = start === -1 ? -1 : indexOf(bytes, close, start);

  if (end === -1) {
    return null;
  }

  let packet: string;

  try {
    packet = new TextDecoder('utf-8', {fatal: true}).decode(
      bytes.subarray(start, end + close.length)
    );
  } catch {
    return null;
  }

  const document = new DOMParser().parseFromString(packet, 'application/xml');

  return document.getElementsByTagName('parsererror').length ? null : document;
}

function elements(root: Document | Element, property: string): Element[] {
  const [prefix, name] = property.split(':') as [string, string];

  return [...root.getElementsByTagNameNS(NAMESPACES[prefix]!, name)];
}

function children(element: Element, property: string): Element[] {
  const [prefix, name] = property.split(':') as [string, string];

  return [...element.children].filter(
    (child) =>
      child.namespaceURI === NAMESPACES[prefix] && child.localName === name
  );
}

/** Returns an XMP property's text, preferring the default language's for properties that have several. */
function xmpText(xmp: Document, property: string): string | null {
  const alternatives = elements(xmp, property).flatMap((element) =>
    children(element, 'rdf:Alt').flatMap((alt) => children(alt, 'rdf:li'))
  );
  const [prefix, name] = property.split(':') as [string, string];
  const candidates = [
    alternatives.filter(
      (item) => item.getAttributeNS(XML_NAMESPACE, 'lang') === 'x-default'
    ),
    alternatives,
    elements(xmp, property).filter(
      (element) => children(element, 'rdf:Alt').length === 0
    ),
    elements(xmp, 'rdf:Description').flatMap((description) => {
      const attribute = description.getAttributeNodeNS(
        NAMESPACES[prefix]!,
        name
      );

      return attribute ? [attribute] : [];
    }),
  ];

  for (const nodes of candidates) {
    for (const node of nodes) {
      const value = text(withoutNulText(node.textContent ?? ''));

      if (value !== null) {
        return value;
      }
    }
  }

  return null;
}

/** Returns the items of an XMP list property, such as its creators. */
function xmpList(xmp: Document, property: string): string[] {
  const values = elements(xmp, property)
    .flatMap((element) =>
      [...element.children].filter(
        (child) =>
          child.namespaceURI === NAMESPACES.rdf &&
          (child.localName === 'Seq' || child.localName === 'Bag')
      )
    )
    .flatMap((list) => children(list, 'rdf:li'))
    .map((item) => text(withoutNulText(item.textContent ?? '')))
    .filter((value): value is string => value !== null);

  if (values.length === 0) {
    const value = xmpText(xmp, property);

    if (value !== null) {
      values.push(value);
    }
  }

  return values;
}

interface Segment {
  marker: number;
  data: Uint8Array;
}

/** The marker segments at the start of a JPEG, up to where its image data starts. */
function jpegSegments(bytes: Uint8Array): Segment[] {
  const segments: Segment[] = [];
  let offset = 2;

  while (offset + 4 <= bytes.length && bytes[offset] === 0xff) {
    const marker = bytes[offset + 1]!;

    // Start of scan: the image data follows
    if (marker === 0xda || marker === 0xd9) {
      break;
    }

    const length = (bytes[offset + 2]! << 8) | bytes[offset + 3]!;

    if (length < 2 || offset + 2 + length > bytes.length) {
      break;
    }

    segments.push({
      marker,
      data: bytes.subarray(offset + 4, offset + 2 + length),
    });
    offset += 2 + length;
  }

  return segments;
}

/** Parses the first APP13 segment's IPTC records, as PHP's `iptcparse()` does. */
function iptc(segments: Segment[]): Iptc {
  const data = segments.find(({marker}) => marker === 0xed)?.data;
  const records: Iptc = {};

  if (!data) {
    return records;
  }

  let offset = 0;

  while (
    offset < data.length &&
    !(
      data[offset] === 0x1c &&
      (data[offset + 1] === 1 || data[offset + 1] === 2)
    )
  ) {
    offset++;
  }

  while (offset + 5 <= data.length && data[offset] === 0x1c) {
    const record = data[offset + 1]!;
    const dataset = data[offset + 2]!;
    let length = (data[offset + 3]! << 8) | data[offset + 4]!;
    offset += 5;

    // An extended dataset gives its length in the next four bytes
    if (length & 0x8000) {
      if (offset + 4 > data.length) {
        break;
      }

      length =
        ((data[offset]! << 24) |
          (data[offset + 1]! << 16) |
          (data[offset + 2]! << 8) |
          data[offset + 3]!) >>>
        0;
      offset += 4;
    }

    if (offset + length > data.length) {
      break;
    }

    const tag = `${record}#${String(dataset).padStart(3, '0')}`;
    (records[tag] ??= []).push(data.subarray(offset, offset + length));
    offset += length;
  }

  return records;
}

const EXIF_TAGS: Record<number, string> = {
  0x010f: 'Make',
  0x0110: 'Model',
  0x013b: 'Artist',
  0x8298: 'Copyright',
  0x9003: 'DateTimeOriginal',
  0x9011: 'OffsetTimeOriginal',
};
const GPS_LATITUDE_REF = 0x0001;
const GPS_LATITUDE = 0x0002;
const GPS_LONGITUDE_REF = 0x0003;
const GPS_LONGITUDE = 0x0004;
const EXIF_IFD_POINTER = 0x8769;
const GPS_IFD_POINTER = 0x8825;
const TYPE_SIZES: Record<number, number> = {
  1: 1,
  2: 1,
  3: 2,
  4: 4,
  5: 8,
  6: 1,
  7: 1,
  8: 2,
  9: 4,
  10: 8,
  11: 4,
  12: 8,
};

/** Reads the EXIF tags the server uses from a JPEG's first EXIF segment. */
function exif(segments: Segment[]): ExifData {
  const header = 'Exif\0\0';
  const data = segments
    .find(
      ({marker, data}) =>
        marker === 0xe1 &&
        String.fromCharCode(...data.subarray(0, header.length)) === header
    )
    ?.data.subarray(header.length);
  const tags: Exif = {};
  const gpsValues: Record<number, number[]> = {};
  const gpsRefs: Record<number, string> = {};

  if (!data || data.length < 8) {
    return {tags, latitude: null, longitude: null};
  }

  const view = new DataView(data.buffer, data.byteOffset, data.byteLength);
  const little = data[0] === 0x49;
  const visited = new Set<number>();
  const readIfd = (start: number, gps: boolean) => {
    if (visited.has(start) || start + 2 > data.length) {
      return;
    }

    visited.add(start);
    const count = view.getUint16(start, little);

    for (let index = 0; index < count; index++) {
      const entry = start + 2 + index * 12;

      if (entry + 12 > data.length) {
        return;
      }

      const tag = view.getUint16(entry, little);
      const type = view.getUint16(entry + 2, little);
      const length =
        view.getUint32(entry + 4, little) * (TYPE_SIZES[type] ?? 1);
      const valueOffset =
        length <= 4 ? entry + 8 : view.getUint32(entry + 8, little);

      if (gps) {
        if (
          (tag === GPS_LATITUDE || tag === GPS_LONGITUDE) &&
          type === 5 &&
          view.getUint32(entry + 4, little) === 3 &&
          valueOffset + 24 <= data.length
        ) {
          gpsValues[tag] = [0, 1, 2].map((part) => {
            const denominator = view.getUint32(
              valueOffset + part * 8 + 4,
              little
            );

            return denominator
              ? view.getUint32(valueOffset + part * 8, little) / denominator
              : NaN;
          });
        } else if (
          (tag === GPS_LATITUDE_REF || tag === GPS_LONGITUDE_REF) &&
          type === 2
        ) {
          gpsRefs[tag] = latin1(
            withoutNul(data.subarray(valueOffset, valueOffset + length))
          ).trim();
        }
      } else if (tag === EXIF_IFD_POINTER || tag === GPS_IFD_POINTER) {
        readIfd(view.getUint32(entry + 8, little), tag === GPS_IFD_POINTER);
      } else if (
        EXIF_TAGS[tag] &&
        type === 2 &&
        valueOffset + length <= data.length
      ) {
        tags[EXIF_TAGS[tag]] = data.subarray(valueOffset, valueOffset + length);
      }
    }
  };

  readIfd(view.getUint32(4, little), false);

  const coordinate = (
    values: number[] | undefined,
    ref: string | undefined
  ) => {
    if (!values || ref === undefined || values.some(Number.isNaN)) {
      return null;
    }

    const degrees = values[0]! + values[1]! / 60 + values[2]! / 3600;

    return ['S', 'W'].includes(ref.toUpperCase()) ? -degrees : degrees;
  };

  return {
    tags,
    latitude: coordinate(gpsValues[GPS_LATITUDE], gpsRefs[GPS_LATITUDE_REF]),
    longitude: coordinate(gpsValues[GPS_LONGITUDE], gpsRefs[GPS_LONGITUDE_REF]),
  };
}

/** Converts an XMP GPS coordinate, such as `48,16.5N` or `48,16,30N`, to decimal degrees. */
function xmpCoordinate(value: string | null): number | null {
  const match = value?.match(
    /^(\d+),(\d+(?:\.\d+)?)(?:,(\d+(?:\.\d+)?))?([NSEW])$/i
  );

  if (!match) {
    return null;
  }

  const degrees =
    Number(match[1]) + Number(match[2]) / 60 + Number(match[3] || 0) / 3600;

  return ['S', 'W'].includes(match[4]!.toUpperCase()) ? -degrees : degrees;
}

/** Rounds a coordinate to six decimal places, about 10 cm, as the server does. */
function roundCoordinate(value: number): number {
  return Math.round(value * 1e6) / 1e6;
}

function iptcText(iptcData: Iptc, tag: string): string | null {
  const values = iptcData[tag] ?? [];

  if (values.length === 0) {
    return null;
  }

  // Joined as bytes, as the server joins them before decoding
  const space = new Uint8Array([0x20]);
  const joined = values.flatMap((value, index) =>
    index ? [space, value] : [value]
  );

  return decode(concat(joined));
}

/**
 * Converts a metadata value to text. Values that aren't UTF-8 already are
 * usually Windows-1252, which IPTC and EXIF text often is when the file
 * doesn't say what it is.
 */
function decode(bytes: Uint8Array | undefined): string | null {
  if (bytes === undefined) {
    return null;
  }

  const value = withoutNul(bytes);

  try {
    return text(new TextDecoder('utf-8', {fatal: true}).decode(value));
  } catch {
    return text(new TextDecoder('windows-1252').decode(value));
  }
}

/** Trims a value and cuts it to the most characters kept, or returns `null` if it's blank. */
function text(value: string): string | null {
  // Cut by code point, as the server's `mb_substr()` does
  const trimmed = phpTrim(Array.from(value).slice(0, MAX_LENGTH).join(''));

  return trimmed !== '' ? trimmed : null;
}

/** Trims the characters PHP's `trim()` does, which are fewer than `String.prototype.trim()`. */
function phpTrim(value: string): string {
  const trimmed = (character: string | undefined) =>
    character !== undefined && PHP_TRIM_CHARACTERS.includes(character);
  let start = 0;
  let end = value.length;

  while (start < end && trimmed(value[start])) {
    start++;
  }

  while (end > start && trimmed(value[end - 1])) {
    end--;
  }

  return value.slice(start, end);
}

function first(...values: (string | null | undefined)[]): string | null {
  return (
    values.find(
      (value) => value !== null && value !== undefined && value !== ''
    ) ?? null
  );
}

function withoutNul(bytes: Uint8Array): Uint8Array {
  return bytes.includes(0) ? bytes.filter((byte) => byte !== 0) : bytes;
}

function withoutNulText(value: string): string {
  return value.replaceAll('\0', '');
}

function latin1(bytes: Uint8Array): string {
  return String.fromCharCode(...bytes);
}

function concat(parts: Uint8Array[]): Uint8Array {
  const joined = new Uint8Array(
    parts.reduce((sum, part) => sum + part.length, 0)
  );
  let offset = 0;

  for (const part of parts) {
    joined.set(part, offset);
    offset += part.length;
  }

  return joined;
}

function indexOf(bytes: Uint8Array, search: string, from = 0): number {
  const needle = Array.from({length: search.length}, (_, index) =>
    search.charCodeAt(index)
  );

  outer: for (
    let index = from;
    index <= bytes.length - needle.length;
    index++
  ) {
    for (let offset = 0; offset < needle.length; offset++) {
      if (bytes[index + offset] !== needle[offset]) {
        continue outer;
      }
    }

    return index;
  }

  return -1;
}
