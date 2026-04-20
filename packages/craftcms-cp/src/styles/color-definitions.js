import {Theme, Color, BackgroundColor} from '@adobe/leonardo-contrast-colors';

const lightContrastRatios = [
  1, 1.2, 1.4, 2, 3, 4.5, 5, 7.46, 10.21, 13.58, 17.04,
];

const colorDefaults = {
  ratios: lightContrastRatios,
};

const gray = new Color({
  ...colorDefaults,
  name: 'gray',
  colorKeys: ['#e4e6ea', '#f9fafb', '#9aa0ad', '#4c5463', '#6b7180', '#101727'],
});

const red = new Color({
  ...colorDefaults,
  name: 'red',
  colorKeys: ['#fcf2f2', '#f6cbca', '#ec6e6b', '#d32d22', '#77221f', '#3f0d0b'],
});

const orange = new Color({
  ...colorDefaults,
  name: 'orange',
  colorKeys: ['#fdf7ed', '#3e160a', '#f8d7ac', '#923615', '#ef8f35', '#e15627'],
});

const amber = new Color({
  ...colorDefaults,
  name: 'amber',
  colorKeys: ['#fefaec', '#401b06', '#fae692', '#8b4217', '#f4bb40', '#f09e38'],
});

const emerald = new Color({
  ...colorDefaults,
  name: 'emerald',
  colorKeys: ['#effcf5', '#b5f1d0', '#5fd096', '#439669', '#1e4d3c', '#347857'],
});

const blue = new Color({
  ...colorDefaults,
  name: 'blue',
  colorKeys: [
    '#f0f6fe',
    '#437df6',
    '#223bb1',
    '#2245dd',
    '#c3dafb',
    '#65a0f7',
    '#192352',
  ],
});

const yellow = new Color({
  ...colorDefaults,
  name: 'yellow',
  colorKeys: [
    '#fefce9',
    '#fcf093',
    '#f5c843',
    '#e5b43d',
    '#c4892f',
    '#804e17',
    '#9c611f',
  ],
});

const slate = new Color({
  ...colorDefaults,
  name: 'slate',
  colorKeys: ['#f2f5f8', '#cbd5e0', '#64728b', '#48546a', '#1f283a'],
});

const lime = new Color({
  ...colorDefaults,
  name: 'lime',
  colorKeys: ['#f8fde8', '#def7a3', '#aae346', '#6ea32f', '#456119', '#1d2d0a'],
});

const green = new Color({
  ...colorDefaults,
  name: 'green',
  colorKeys: [
    '#f2fcf4',
    '#c5f6d1',
    '#65db7d',
    '#5ac65f',
    '#38803e',
    '#25522e',
    '#49a34b',
  ],
});

const teal = new Color({
  ...colorDefaults,
  name: 'teal',
  colorKeys: [
    '#f2fcf9',
    '#acf4e4',
    '#60d1be',
    '#53b7a7',
    '#419389',
    '#265d59',
    '#102d2d',
  ],
});

const cyan = new Color({
  ...colorDefaults,
  name: 'cyan',
  colorKeys: [
    '#effdfe',
    '#b4f1fb',
    '#80e6fa',
    '#5fcfee',
    '#3f8fb3',
    '#317391',
    '#265d75',
    '#143243',
  ],
});

const sky = new Color({
  ...colorDefaults,
  name: 'sky',
  colorKeys: [
    '#f1f8fe',
    '#e2f1fc',
    '#8bd1fa',
    '#54b9f9',
    '#3982cb',
    '#245786',
    '#122d47',
  ],
});

const zinc = new Color({
  ...colorDefaults,
  name: 'zinc',
  colorKeys: [
    '#f9f9f9',
    '#f3f3f4',
    '#d3d3d7',
    '#71717a',
    '#51515b',
    '#08080b',
    '#3e3e45',
  ],
});

const violet = new Color({
  ...colorDefaults,
  name: 'violet',
  colorKeys: [
    '#f4f2fe',
    '#dbd6fb',
    '#a085f7',
    '#7529f4',
    '#6615dd',
    '#461a93',
    '#2a0e63',
    '#8553f5',
  ],
});

const purple = new Color({
  ...colorDefaults,
  name: 'purple',
  colorKeys: [
    '#f8f4fe',
    '#f0e7fd',
    '#d3b3f9',
    '#a04cf6',
    '#8a20f0',
    '#7614d2',
    '#511b85',
    '#360861',
  ],
});

const fuchsia = new Color({
  ...colorDefaults,
  name: 'fuchsia',
  colorKeys: [
    '#fbf4fe',
    '#efd0fb',
    '#e8abf9',
    '#dc71f7',
    '#b725d6',
    '#991eb0',
    '#681c73',
  ],
});

const pink = new Color({
  ...colorDefaults,
  name: 'pink',
  colorKeys: [
    '#fbf2f7',
    '#f4cfe6',
    '#e96db3',
    '#e24697',
    '#d22d75',
    '#951c4b',
    '#7a1d42',
  ],
});

const rose = new Color({
  ...colorDefaults,
  name: 'rose',
  colorKeys: [
    '#fcf1f2',
    '#fae4e6',
    '#f1a5ad',
    '#ea3d5a',
    '#d82e44',
    '#b6253a',
    '#971d37',
    '#460918',
  ],
});

const neutral = new Color({
  ...colorDefaults,
  name: 'neutral',
  colorKeys: [
    '#f9f9f9',
    '#f4f4f4',
    '#d3d3d3',
    '#a1a1a1',
    '#737373',
    '#3f3f3f',
    '#161616',
  ],
});

const stone = new Color({
  ...colorDefaults,
  name: 'stone',
  colorKeys: ['#fafaf9', '#e6e5e4', '#77716b', '#a4a09b', '#43403b', '#1b1917'],
});

const lightBackground = new BackgroundColor({
  name: 'background',
  colorKeys: ['#F8FAFC'],
  ratios: [1],
});

// Light theme: background is white
export const lightTheme = new Theme({
  colors: [
    gray,
    red,
    orange,
    amber,
    emerald,
    blue,
    yellow,
    slate,
    lime,
    green,
    teal,
    cyan,
    sky,
    zinc,
    violet,
    purple,
    fuchsia,
    pink,
    rose,
    neutral,
    stone,
  ],
  backgroundColor: lightBackground,
  lightness: 97,
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
