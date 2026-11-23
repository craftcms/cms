import type {StatusShape} from '../src/constants/statuses';

/**
 * Points inside a dot's box, as fractions of its width and height, and
 * whether each shape covers them. Every shape covers a different set, so the
 * set of points a dot hit-tests on names its shape.
 */
const probes: Array<[x: number, y: number, covers: StatusShape[]]> = [
  [0.06, 0.2, ['square']],
  [0.08, 0.5, ['circle', 'square', 'diamond']],
  [0.13, 0.92, ['square', 'triangle']],
  [0.2, 0.2, ['circle', 'square']],
];

/**
 * The shape a dot is drawn as, read from where it hit-tests, which follows its
 * rendered `border-radius` and `clip-path`. Draw the dot at least 40px wide.
 */
export function renderedShape(dot: HTMLElement): StatusShape | null {
  const root = dot.getRootNode() as ShadowRoot;
  const rect = dot.getBoundingClientRect();
  if (rect.width < 40) {
    throw new Error(`The dot is ${rect.width}px wide, too small to probe.`);
  }
  const hits = probes.map(
    ([x, y]) =>
      root.elementFromPoint(
        rect.left + rect.width * x,
        rect.top + rect.height * y
      ) === dot
  );
  const shapes: StatusShape[] = ['circle', 'square', 'triangle', 'diamond'];

  return (
    shapes.find((shape) =>
      probes.every(([, , covers], i) => covers.includes(shape) === hits[i])
    ) ?? null
  );
}
