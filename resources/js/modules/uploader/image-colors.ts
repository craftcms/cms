/**
 * Samples an image file in the browser, the same way
 * `CraftCms\Cms\Image\Images::sample()` does on the server, so uploads can
 * send its colors and hash along instead of the server downloading and
 * decoding the file to find them.
 */

/** Color data sampled from an image, as `CraftCms\Cms\Image\Data\ImageColors` holds it. */
export interface ImageColors {
  dominant: string | null;
  grid: string[][];
  /** The image's main colors, most dominant first. */
  palette: string[];
  /**
   * How light the darker and lighter parts of each region are, as their 10th
   * and 90th percentile WCAG relative luminance, or `null` for regions with
   * no mostly opaque pixels.
   */
  luminance: ([number, number] | null)[][];
}

/** What was sampled from an image, as `CraftCms\Cms\Image\Data\ImageSample` holds it. */
export type ImageSample = {
  colors: ImageColors;
  /** The image's difference hash, as 16 hexadecimal digits. */
  hash: string;
};

export interface Pixels {
  data: Uint8ClampedArray;
  width: number;
  height: number;
}

type Oklab = [number, number, number];

/** The longest side, in pixels, images are scaled down to before their colors are sampled. */
const SAMPLE_SIZE = 100;
const GRID_LONG_SIDE = 4;
const GRID_SHORT_SIDE = 3;
const DOMINANT_CANDIDATES = 5;
const BLACK_LIGHTNESS = 0.2;
const WHITE_LIGHTNESS = 0.95;
const MAX_ITERATIONS = 50;
const CONVERGENCE_THRESHOLD = 0.001;
const MIN_CLUSTER_SIZE_PERCENT = 1;
const SEED = 1024;
const HASH_COLUMNS = 9;
const HASH_ROWS = 8;

/**
 * Samples an image file, or resolves to `null` if the browser can't decode it,
 * leaving the server to sample it instead.
 */
export async function readImageSample(file: File): Promise<ImageSample | null> {
  if (!file.type.startsWith('image/') || file.type === 'image/svg+xml') {
    return null;
  }

  try {
    const bitmap = await createImageBitmap(file);

    try {
      const scale = Math.min(
        1,
        SAMPLE_SIZE / Math.max(bitmap.width, bitmap.height)
      );
      const width = Math.max(1, Math.round(bitmap.width * scale));
      const height = Math.max(1, Math.round(bitmap.height * scale));
      const context = new OffscreenCanvas(width, height).getContext('2d', {
        willReadFrequently: true,
      });

      if (!context) {
        return null;
      }

      context.imageSmoothingQuality = 'high';
      context.drawImage(bitmap, 0, 0, width, height);

      return sampleImage(context.getImageData(0, 0, width, height));
    } finally {
      bitmap.close();
    }
  } catch {
    return null;
  }
}

/**
 * Completion data for asset uploads: the file's colors and hash, so the server
 * can store them without sampling the file itself.
 */
export async function imageSampleCompletionData(
  file: File
): Promise<ImageSample | null> {
  return readImageSample(file);
}

/** Samples an image's colors and difference hash. */
export function sampleImage(pixels: Pixels): ImageSample {
  return {colors: sampleImageColors(pixels), hash: imageHash(pixels)};
}

/**
 * Samples an image's dominant color and the average colors of its regions:
 * 4×3 regions for landscape and square images, and 3×4 for portrait ones.
 */
export function sampleImageColors(pixels: Pixels): ImageColors {
  const candidates = dominantCandidates(pixels);
  const dominant =
    candidates.find(
      ([lightness]) =>
        lightness >= BLACK_LIGHTNESS && lightness <= WHITE_LIGHTNESS
    ) ?? candidates[0];
  const toHex = (color: Oklab) => hex(...fromOklab(color), 255);

  return {
    dominant: dominant ? toHex(dominant) : null,
    grid: colorGrid(pixels),
    palette: [...new Set(candidates.map(toHex))],
    luminance: luminanceGrid(pixels),
  };
}

function gridSize({width, height}: Pixels): [number, number] {
  return width >= height
    ? [GRID_LONG_SIDE, GRID_SHORT_SIDE]
    : [GRID_SHORT_SIDE, GRID_LONG_SIDE];
}

function colorGrid(pixels: Pixels): string[][] {
  const {data, width, height} = pixels;
  const [columns, rows] = gridSize(pixels);
  const grid: string[][] = [];

  for (let row = 0; row < rows; row++) {
    const top = (row * height) / rows;
    const bottom = ((row + 1) * height) / rows;
    const cells: string[] = [];

    for (let column = 0; column < columns; column++) {
      const left = (column * width) / columns;
      const right = ((column + 1) * width) / columns;
      let weights = 0;
      let alpha = 0;
      let red = 0;
      let green = 0;
      let blue = 0;

      // Colors mix in linear light, so fine black and white detail averages to
      // the gray it reads as. Pixels that straddle a region's edge count
      // toward it by how much of them it covers.
      for (let y = Math.floor(top); y < Math.ceil(bottom); y++) {
        const coverageY = Math.min(y + 1, bottom) - Math.max(y, top);

        for (let x = Math.floor(left); x < Math.ceil(right); x++) {
          const weight =
            (Math.min(x + 1, right) - Math.max(x, left)) * coverageY;
          const index = (y * width + x) * 4;
          const opacity = (data[index + 3]! / 255) * weight;

          weights += weight;
          alpha += opacity;
          red += toLinear(data[index]!) * opacity;
          green += toLinear(data[index + 1]!) * opacity;
          blue += toLinear(data[index + 2]!) * opacity;
        }
      }

      cells.push(
        hex(
          alpha ? toSrgb(red / alpha) : 0,
          alpha ? toSrgb(green / alpha) : 0,
          alpha ? toSrgb(blue / alpha) : 0,
          (alpha / weights) * 255
        )
      );
    }

    grid.push(cells);
  }

  return grid;
}

/**
 * Finds the 10th and 90th percentile relative luminance of each region's
 * pixels. Each pixel counts toward the region its center is in, and mostly
 * transparent pixels are left out.
 */
function luminanceGrid(pixels: Pixels): ([number, number] | null)[][] {
  const {data, width, height} = pixels;
  const [columns, rows] = gridSize(pixels);
  const cells = Array.from({length: rows}, () =>
    Array.from({length: columns}, (): number[] => [])
  );

  for (let index = 0; index < width * height; index++) {
    if (data[index * 4 + 3]! / 255 < 0.5) {
      continue;
    }

    const x = index % width;
    const y = Math.floor(index / width);
    const column = Math.min(
      columns - 1,
      Math.floor(((x + 0.5) * columns) / width)
    );
    const row = Math.min(rows - 1, Math.floor(((y + 0.5) * rows) / height));

    cells[row]![column]!.push(
      relativeLuminance(
        data[index * 4]!,
        data[index * 4 + 1]!,
        data[index * 4 + 2]!
      )
    );
  }

  return cells.map((row) =>
    row.map((values) => {
      if (!values.length) {
        return null;
      }

      values.sort((a, b) => a - b);

      return [
        round(percentile(values, 0.1), 3),
        round(percentile(values, 0.9), 3),
      ];
    })
  );
}

/** Returns a percentile of sorted values, interpolating between the two nearest ones. */
function percentile(sorted: number[], fraction: number): number {
  const position = (sorted.length - 1) * fraction;
  const lower = Math.floor(position);
  const upper = Math.min(sorted.length - 1, lower + 1);

  return (
    sorted[lower]! + (sorted[upper]! - sorted[lower]!) * (position - lower)
  );
}

/**
 * Returns the image's difference hash, as `CraftCms\Cms\Image\ImageHash`
 * does: the image is averaged down to 9×8 regions of lightness, with
 * transparent pixels treated as white, and each bit says whether a region is
 * lighter than the one to its right.
 */
export function imageHash({data, width, height}: Pixels): string {
  let hash = '';

  for (let row = 0; row < HASH_ROWS; row++) {
    const top = (row * height) / HASH_ROWS;
    const bottom = ((row + 1) * height) / HASH_ROWS;
    const lightness: number[] = [];

    for (let column = 0; column < HASH_COLUMNS; column++) {
      const left = (column * width) / HASH_COLUMNS;
      const right = ((column + 1) * width) / HASH_COLUMNS;
      let weights = 0;
      let sum = 0;

      for (let y = Math.floor(top); y < Math.ceil(bottom); y++) {
        const coverageY = Math.min(y + 1, bottom) - Math.max(y, top);

        for (let x = Math.floor(left); x < Math.ceil(right); x++) {
          const weight =
            (Math.min(x + 1, right) - Math.max(x, left)) * coverageY;
          const index = (y * width + x) * 4;
          const alpha = data[index + 3]! / 255;
          const luminance = relativeLuminance(
            data[index]!,
            data[index + 1]!,
            data[index + 2]!
          );

          weights += weight;
          sum += (luminance * alpha + 1 - alpha) * weight;
        }
      }

      lightness.push(weights > 0 ? sum / weights : 1);
    }

    let nibble = 0;

    for (let column = 0; column < HASH_COLUMNS - 1; column++) {
      nibble =
        (nibble << 1) | (lightness[column]! < lightness[column + 1]! ? 1 : 0);

      if (column % 4 === 3) {
        hash += nibble.toString(16);
        nibble = 0;
      }
    }
  }

  return hash;
}

/** Returns the WCAG relative luminance of an sRGB color, from 0 (black) to 1 (white). */
function relativeLuminance(red: number, green: number, blue: number): number {
  return (
    0.2126 * toLinear(red) + 0.7152 * toLinear(green) + 0.0722 * toLinear(blue)
  );
}

/** Rounds half away from zero, as PHP's `round()` does. */
function round(value: number, precision: number): number {
  const factor = 10 ** precision;

  return (Math.sign(value) * Math.round(Math.abs(value) * factor)) / factor;
}

/**
 * Clusters the image's colors with k-means in Oklab, as Intervention Image's
 * dominant palette analysis does, and returns the largest clusters. The
 * dominant color passes over near-black and near-white ones in favor of the
 * most dominant other one, if there is one.
 */
function dominantCandidates({data}: Pixels): Oklab[] {
  const points: Oklab[] = [];

  for (let index = 0; index < data.length; index += 4) {
    if (data[index + 3] !== 0) {
      points.push(toOklab(data[index]!, data[index + 1]!, data[index + 2]!));
    }
  }

  return clusterColors(points).slice(0, DOMINANT_CANDIDATES);
}

/** Returns the centroids of the points' clusters, largest first. */
function clusterColors(points: Oklab[]): Oklab[] {
  const random = seededRandom(SEED);
  let centroids = initialCentroids(
    points,
    Math.min(DOMINANT_CANDIDATES, points.length),
    random
  );
  let assignments: number[] = [];

  for (let iteration = 0; iteration < MAX_ITERATIONS; iteration++) {
    assignments = points.map((point) => nearest(point, centroids));
    const updated = centroids.map((centroid, cluster) => {
      const members = points.filter(
        (_, index) => assignments[index] === cluster
      );

      if (!members.length) {
        return points[Math.floor(random() * points.length)]!;
      }

      return [0, 1, 2].map(
        (channel) =>
          members.reduce((sum, member) => sum + member[channel]!, 0) /
          members.length
      ) as Oklab;
    });
    const converged = centroids.every(
      (centroid, cluster) =>
        squaredDistance(centroid, updated[cluster]!) <=
        CONVERGENCE_THRESHOLD ** 2
    );

    if (converged) {
      break;
    }

    centroids = updated;
  }

  const minSize = (points.length * MIN_CLUSTER_SIZE_PERCENT) / 100;

  return centroids
    .map((centroid, cluster) => ({
      centroid,
      size: assignments.filter((assignment) => assignment === cluster).length,
    }))
    .filter(({size}) => size >= minSize)
    .sort((a, b) => b.size - a.size)
    .map(({centroid}) => centroid);
}

/** Picks well-spread starting centroids with k-means++. */
function initialCentroids(
  points: Oklab[],
  count: number,
  random: () => number
): Oklab[] {
  if (!points.length) {
    return [];
  }

  const centroids = [points[Math.floor(random() * points.length)]!];

  while (centroids.length < count) {
    const distances = points.map((point) =>
      Math.min(...centroids.map((centroid) => squaredDistance(point, centroid)))
    );
    const total = distances.reduce((sum, distance) => sum + distance, 0);

    if (total === 0) {
      break;
    }

    const target = random() * total;
    let cumulative = 0;
    let chosen = 0;

    for (const [index, distance] of distances.entries()) {
      cumulative += distance;

      if (cumulative >= target) {
        chosen = index;
        break;
      }
    }

    centroids.push(points[chosen]!);
  }

  return centroids;
}

function nearest(point: Oklab, centroids: Oklab[]): number {
  let closest = 0;
  let closestDistance = Infinity;

  for (const [index, centroid] of centroids.entries()) {
    const distance = squaredDistance(point, centroid);

    if (distance < closestDistance) {
      closestDistance = distance;
      closest = index;
    }
  }

  return closest;
}

function squaredDistance(a: Oklab, b: Oklab): number {
  return (a[0] - b[0]) ** 2 + (a[1] - b[1]) ** 2 + (a[2] - b[2]) ** 2;
}

/** A small seeded PRNG (mulberry32), so the same image always samples the same way. */
function seededRandom(seed: number): () => number {
  let state = seed;

  return () => {
    state = (state + 0x6d2b79f5) | 0;
    let t = Math.imul(state ^ (state >>> 15), 1 | state);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;

    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

function toOklab(red: number, green: number, blue: number): Oklab {
  const [r, g, b] = [toLinear(red), toLinear(green), toLinear(blue)];
  const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
  const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
  const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);

  return [
    0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s,
    1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s,
    0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s,
  ];
}

function fromOklab([lightness, a, b]: Oklab): [number, number, number] {
  const l = (lightness + 0.3963377774 * a + 0.2158037573 * b) ** 3;
  const m = (lightness - 0.1055613458 * a - 0.0638541728 * b) ** 3;
  const s = (lightness - 0.0894841775 * a - 1.291485548 * b) ** 3;

  return [
    4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
    -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
    -0.0041960863 * l - 0.7034186147 * m + 1.707614701 * s,
  ].map(toSrgb) as [number, number, number];
}

/** Converts an sRGB channel from 0 to 255 to linear light, from 0 to 1. */
function toLinear(channel: number): number {
  const value = channel / 255;

  return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
}

/** Converts linear light from 0 to 1 to an sRGB channel from 0 to 255. */
function toSrgb(linear: number): number {
  const value = Math.min(1, Math.max(0, linear));

  return (
    (value <= 0.0031308 ? value * 12.92 : 1.055 * value ** (1 / 2.4) - 0.055) *
    255
  );
}

/** Formats a color as `#rrggbb`, or `#rrggbbaa` if it isn't fully opaque. */
function hex(red: number, green: number, blue: number, alpha: number): string {
  const channels = [red, green, blue];

  if (Math.round(alpha) < 255) {
    channels.push(alpha);
  }

  return `#${channels
    .map((channel) =>
      Math.round(Math.min(255, Math.max(0, channel)))
        .toString(16)
        .padStart(2, '0')
    )
    .join('')}`;
}
