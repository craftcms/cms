/**
 * Samples the colors of an image file in the browser, the same way
 * `CraftCms\Cms\Image\Images::colors()` does on the server, so uploads can
 * send their colors along instead of the server downloading and decoding the
 * file to find them.
 */

/** Color data sampled from an image, as `CraftCms\Cms\Image\Data\ImageColors` holds it. */
export interface ImageColors {
  dominant: string | null;
  grid: string[][];
}

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

/**
 * Samples an image file's colors, or resolves to `null` if the browser can't
 * decode it, leaving the server to sample it instead.
 */
export async function readImageColors(file: File): Promise<ImageColors | null> {
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

      return sampleImageColors(context.getImageData(0, 0, width, height));
    } finally {
      bitmap.close();
    }
  } catch {
    return null;
  }
}

/**
 * Completion data for asset uploads: the file's colors, so the server can
 * store them without sampling the file itself.
 */
export async function imageColorsCompletionData(
  file: File
): Promise<{colors: ImageColors} | null> {
  const colors = await readImageColors(file);

  return colors ? {colors} : null;
}

/**
 * Samples an image's dominant color and the average colors of its regions:
 * 4×3 regions for landscape and square images, and 3×4 for portrait ones.
 */
export function sampleImageColors(pixels: Pixels): ImageColors {
  return {
    dominant: dominantColor(pixels),
    grid: colorGrid(pixels),
  };
}

function colorGrid({data, width, height}: Pixels): string[][] {
  const [columns, rows] =
    width >= height
      ? [GRID_LONG_SIDE, GRID_SHORT_SIDE]
      : [GRID_SHORT_SIDE, GRID_LONG_SIDE];
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

      // Pixels that straddle a region's edge count toward it by how much of them it covers.
      for (let y = Math.floor(top); y < Math.ceil(bottom); y++) {
        const coverageY = Math.min(y + 1, bottom) - Math.max(y, top);

        for (let x = Math.floor(left); x < Math.ceil(right); x++) {
          const weight =
            (Math.min(x + 1, right) - Math.max(x, left)) * coverageY;
          const index = (y * width + x) * 4;
          const opacity = (data[index + 3]! / 255) * weight;

          weights += weight;
          alpha += opacity;
          red += data[index]! * opacity;
          green += data[index + 1]! * opacity;
          blue += data[index + 2]! * opacity;
        }
      }

      cells.push(
        hex(
          alpha ? red / alpha : 0,
          alpha ? green / alpha : 0,
          alpha ? blue / alpha : 0,
          (alpha / weights) * 255
        )
      );
    }

    grid.push(cells);
  }

  return grid;
}

/**
 * Clusters the image's colors with k-means in Oklab, as Intervention Image's
 * dominant palette analysis does, then passes over near-black and near-white
 * clusters in favor of the most dominant other one, if there is one.
 */
function dominantColor({data}: Pixels): string | null {
  const points: Oklab[] = [];

  for (let index = 0; index < data.length; index += 4) {
    if (data[index + 3] !== 0) {
      points.push(toOklab(data[index]!, data[index + 1]!, data[index + 2]!));
    }
  }

  const candidates = clusterColors(points).slice(0, DOMINANT_CANDIDATES);
  const dominant =
    candidates.find(
      ([lightness]) =>
        lightness >= BLACK_LIGHTNESS && lightness <= WHITE_LIGHTNESS
    ) ?? candidates[0];

  return dominant ? hex(...fromOklab(dominant), 255) : null;
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
  const [r, g, b] = [red, green, blue].map((channel) => {
    const value = channel / 255;

    return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
  }) as [number, number, number];
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
  ].map((channel) => {
    const value = Math.min(1, Math.max(0, channel));
    const gamma =
      value <= 0.0031308 ? value * 12.92 : 1.055 * value ** (1 / 2.4) - 0.055;

    return gamma * 255;
  }) as [number, number, number];
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
