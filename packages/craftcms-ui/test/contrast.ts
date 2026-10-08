/**
 * Paints `color` over `backdrop` and returns the resulting pixel, so a
 * translucent colour is measured as it is actually seen.
 */
function paint(color: string, backdrop = '#fff'): [number, number, number] {
  const context = document.createElement('canvas').getContext('2d')!;
  context.fillStyle = backdrop;
  context.fillRect(0, 0, 1, 1);
  context.fillStyle = color;
  context.fillRect(0, 0, 1, 1);
  const [red, green, blue] = context.getImageData(0, 0, 1, 1).data;

  return [red!, green!, blue!];
}

function luminance([red, green, blue]: [number, number, number]): number {
  const [r, g, b] = [red, green, blue].map((channel) => {
    const value = channel / 255;

    return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
  });

  return 0.2126 * r! + 0.7152 * g! + 0.0722 * b!;
}

/**
 * The WCAG contrast ratio between `foreground` and `background`, with the
 * foreground composited over the background first.
 */
export function contrast(foreground: string, background: string): number {
  const back = paint(background);
  const front = paint(foreground, background);
  const [lighter, darker] = [luminance(front), luminance(back)].sort(
    (a, b) => b - a
  );

  return (lighter! + 0.05) / (darker! + 0.05);
}

/** Loads the document-level token stylesheets the components read. */
export async function loadTokens(): Promise<void> {
  await import('../src/styles/shared/color-palette.css');
  await import('../src/styles/shared/colorable.css');
  await import('../src/styles/shared/variables.css');
  await import('../src/styles/shared/tokens.css');
}
