import {
  Theme,
  Color,
  BackgroundColor,
  contrast,
} from '@adobe/leonardo-contrast-colors';

const contrastRatios = {
  static: [1.45, 1.78, 2.22, 2.78, 3.54, 4.61, 5.92, 7.81, 10.21, 13.01, 15.91],
  light: [1.08, 1.33, 1.58, 2.39, 3.01, 3.87, 5.07, 6.72, 8.84, 11.31, 13.94],
  dark: [1.08, 1.58, 1.96, 2.45, 3.09, 3.9, 5.07, 6.02, 7.34, 8.77, 10.18],
  base: [-1.2, 1, 1.1, 1.4, 2, 4, 5, 6.5, 10.21, 13.58, 17.04],
};

const slateColorKeys = ['#f3f7fc', '#cdd8e4', '#616d7b', '#3f4d5a', '#1f2933'];

const colorKeys = {
  gray: ['#e4e6ea', '#f9fafb', '#9aa0ad', '#4c5463', '#6b7180', '#101727'],
  red: ['#fcf2f2', '#f6cbca', '#ec6e6b', '#d32d22', '#77221f', '#3f0d0b'],
  orange: ['#fdf7ed', '#3e160a', '#f8d7ac', '#923615', '#ef8f35', '#e15627'],
  amber: ['#fefaec', '#401b06', '#fae692', '#8b4217', '#f4bb40', '#f09e38'],
  emerald: ['#effcf5', '#b5f1d0', '#5fd096', '#439669', '#1e4d3c', '#347857'],
  blue: [
    '#f0f4fe',
    '#437cf6',
    '#224fb1',
    '#225ddd',
    '#c3d5fb',
    '#6593f7',
    '#192b52',
  ],
  yellow: [
    '#fefce9',
    '#fcf093',
    '#f5c843',
    '#e5b43d',
    '#c4892f',
    '#804e17',
    '#9c611f',
  ],
  slate: slateColorKeys,
  lime: ['#f8fde8', '#def7a3', '#aae346', '#6ea32f', '#456119', '#1d2d0a'],
  green: [
    '#f2fcf4',
    '#c5f6d1',
    '#65db7d',
    '#5ac65f',
    '#38803e',
    '#25522e',
    '#49a34b',
  ],
  teal: [
    '#f2fcf9',
    '#acf4e4',
    '#60d1be',
    '#53b7a7',
    '#419389',
    '#265d59',
    '#102d2d',
  ],
  cyan: [
    '#effdfe',
    '#b4f1fb',
    '#80e6fa',
    '#5fcfee',
    '#3f8fb3',
    '#317391',
    '#265d75',
    '#143243',
  ],
  sky: [
    '#f1f8fe',
    '#e2f1fc',
    '#8bd1fa',
    '#54b9f9',
    '#3982cb',
    '#245786',
    '#122d47',
  ],
  zinc: [
    '#f9f9f9',
    '#f3f3f4',
    '#d3d3d7',
    '#71717a',
    '#51515b',
    '#08080b',
    '#3e3e45',
  ],
  violet: [
    '#f4f2fe',
    '#dbd6fb',
    '#a085f7',
    '#7529f4',
    '#6615dd',
    '#461a93',
    '#2a0e63',
    '#8553f5',
  ],
  purple: [
    '#f8f4fe',
    '#f0e7fd',
    '#d3b3f9',
    '#a04cf6',
    '#8a20f0',
    '#7614d2',
    '#511b85',
    '#360861',
  ],
  fuchsia: [
    '#fbf4fe',
    '#efd0fb',
    '#e8abf9',
    '#dc71f7',
    '#b725d6',
    '#991eb0',
    '#681c73',
  ],
  pink: [
    '#fbf2f7',
    '#f4cfe6',
    '#e96db3',
    '#e24697',
    '#d22d75',
    '#951c4b',
    '#7a1d42',
  ],
  rose: [
    '#fcf1f2',
    '#fae4e6',
    '#f1a5ad',
    '#ea3d5a',
    '#d82e44',
    '#b6253a',
    '#971d37',
    '#460918',
  ],
  neutral: [
    '#f9f9f9',
    '#f4f4f4',
    '#d3d3d3',
    '#a1a1a1',
    '#737373',
    '#3f3f3f',
    '#161616',
  ],
  stone: ['#fafaf9', '#e6e5e4', '#77716b', '#a4a09b', '#43403b', '#1b1917'],
  indigo: ['#f7f8fe', '#dde3fc', '#7d85f7', '#4334d3', '#25215f', '#141234'],
  base: slateColorKeys,
};

const backgroundColor = new BackgroundColor({
  name: 'background',
  colorKeys: colorKeys.slate,
  ratios: [1],
});

function makeColors(ratios) {
  return Object.entries(colorKeys).map(
    ([name, keys]) =>
      new Color({
        name,
        colorKeys: keys,
        ratios: name === 'base' ? contrastRatios.base : ratios,
      })
  );
}

export const lightTheme = new Theme({
  colors: makeColors(contrastRatios.light),
  backgroundColor: backgroundColor,
  lightness: 97,
});

export const staticTheme = new Theme({
  colors: makeColors(contrastRatios.static),
  backgroundColor: backgroundColor,
  lightness: 100,
});

export const darkTheme = new Theme({
  colors: makeColors(contrastRatios.dark),
  backgroundColor: backgroundColor,
  lightness: 26,
});

export const stops = [
  '50',
  '100',
  '200',
  '300',
  '400',
  '500',
  '600',
  '700',
  '800',
  '900',
  '950',
];

/*
  Status colors (enabled, pending, expired) are more vivid than the palette, so
  each one has its own key colors rather than a palette stop. A status dot has
  no darker ring, so its fill alone must reach 3:1 (WCAG 1.4.11) against every
  surface it sits on, and the text color must reach 4.5:1.

  Leonardo measures against one background per theme, so each theme targets the
  surface a status color contrasts least with:
  - light: the page surface (`--c-surface-page`, base-200), the darkest one;
  - dark: the lime card header (lime's `--c-color-fill-quiet`), the lightest
    surface or tint a dot sits on.
  Selected card headers are left out. No fill can contrast with both them and
  the page, so a dot there needs a ring instead.

  A key that already passes keeps its contrast, so the output stays the key
  itself. A key that falls short is moved to the target in OKLCH, which keeps
  its hue. If a surface changes, these backgrounds have to change with it;
  status.browser.test.ts measures the dots on the real surfaces.
*/
const statusSurfaces = {light: '#e6ecf4', dark: '#324912'};

// Targets sit above 3 and 4.5. Leonardo can land up to 0.01 under its target,
// and browsers mix and round the surface colors slightly differently.
const statusRatios = {fill: 3.2, text: 4.55};

const statusKeys = {
  light: {
    enabled: {fill: '#009a5f', text: '#007a4a'},
    pending: {fill: '#008ae2', text: '#006db4'},
    expired: {fill: '#ff002f', text: '#d50026'},
  },
  dark: {
    enabled: {fill: '#00ffa1', text: '#00ffa1'},
    pending: {fill: '#009dff', text: '#5eb4ff'},
    expired: {fill: '#ff4a4d', text: '#ff8e87'},
  },
};

const hexToRgb = (hex) =>
  [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));

/**
 * Runs each `{name, key, ratio}` through Leonardo against one exact
 * background. A key that already reaches its ratio is kept as it is; one that
 * falls short is moved to the ratio in OKLCH, which keeps its hue.
 */
function keyedColors(background, specs) {
  const colors = specs.map(({name, key, ratio}) => {
    const keyRatio = Math.abs(contrast(hexToRgb(key), hexToRgb(background)));

    return new Color({
      name,
      colorKeys: [key],
      colorSpace: 'OKLCH',
      ratios: [Math.max(ratio, keyRatio)],
    });
  });

  // A string background makes the theme measure against that exact color.
  return new Theme({colors, backgroundColor: background}).contrastColors
    .slice(1)
    .map(({name, values: [{value}]}) => ({name, value}));
}

function makeStatusColors(theme) {
  return keyedColors(
    statusSurfaces[theme],
    Object.entries(statusKeys[theme]).flatMap(([status, keys]) =>
      Object.entries(keys).map(([part, key]) => ({
        name: `status-${status}-${part}`,
        key,
        ratio: statusRatios[part],
      }))
    )
  );
}

export const statusColors = {
  light: makeStatusColors('light'),
  dark: makeStatusColors('dark'),
};

/*
  A form control's border (inputs, checkboxes, radios) is its only boundary, so
  it needs 3:1 against what's behind it (WCAG 1.4.11). It starts from the
  palette's slate-400 and is darkened (light) or lightened (dark) only as far as
  it takes to reach that on the darkest or lightest backdrop a control sits on:
  the body of a selected white card, tinted with the accent's quiet fill. A
  selected neutral card's body is lighter in the light theme and darker in the
  dark one, so it passes too. Tinted cards recolor their controls' borders.
  card.browser.test.ts measures real controls in every card.
*/
const formControlBackdrops = {light: '#e7eefd', dark: '#1e3e83'};

const slate400 = (theme) =>
  theme.contrastColors.find(({name}) => name === 'slate').values[
    stops.indexOf('400')
  ].value;

export const formControlColors = {
  light: keyedColors(formControlBackdrops.light, [
    {name: 'form-control-border', key: slate400(lightTheme), ratio: 3.2},
  ]),
  dark: keyedColors(formControlBackdrops.dark, [
    {name: 'form-control-border', key: slate400(darkTheme), ratio: 3.2},
  ]),
};
