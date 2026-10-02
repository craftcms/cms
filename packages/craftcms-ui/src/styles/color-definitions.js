import {Theme, Color, BackgroundColor} from '@adobe/leonardo-contrast-colors';

const contrastRatios = {
  static: [1.45, 1.78, 2.22, 2.78, 3.54, 4.61, 5.92, 7.81, 10.21, 13.01, 15.91],
  light: [1.08, 1.33, 1.58, 2.39, 3.01, 3.87, 5.07, 6.72, 8.84, 11.31, 13.94],
  dark: [1.08, 1.58, 1.96, 2.45, 3.09, 3.9, 5.07, 6.02, 7.34, 8.77, 10.18],
  base: [-1.2, 1, 1.2, 1.4, 2, 4, 5, 6.5, 10.21, 13.58, 17.04],
};

const slateColorKeys = ['#f2f5f8', '#cbd5e0', '#64728b', '#48546a', '#1f283a'];

const colorKeys = {
  gray: ['#e4e6ea', '#f9fafb', '#9aa0ad', '#4c5463', '#6b7180', '#101727'],
  red: ['#fcf2f2', '#f6cbca', '#ec6e6b', '#d32d22', '#77221f', '#3f0d0b'],
  orange: ['#fdf7ed', '#3e160a', '#f8d7ac', '#923615', '#ef8f35', '#e15627'],
  amber: ['#fefaec', '#401b06', '#fae692', '#8b4217', '#f4bb40', '#f09e38'],
  emerald: ['#effcf5', '#b5f1d0', '#5fd096', '#439669', '#1e4d3c', '#347857'],
  blue: [
    '#f0f6fe',
    '#437df6',
    '#223bb1',
    '#2245dd',
    '#c3dafb',
    '#65a0f7',
    '#192352',
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
