import {Theme, Color, BackgroundColor} from '@adobe/leonardo-contrast-colors';

const lightContrastRatios = [
  1, 1.2, 1.4, 2, 3, 4.5, 6, 7.46, 10.21, 13.58, 17.04,
];

const gray = new Color({
  name: 'gray',
  colorKeys: ['#e4e6ea', '#f9fafb', '#9aa0ad', '#4c5463', '#6b7180', '#101727'],
  ratios: lightContrastRatios,
});

const red = new Color({
  name: 'red',
  colorKeys: ['#fcf2f2', '#f6cbca', '#ec6e6b', '#d32d22', '#77221f', '#3f0d0b'],
  ratios: lightContrastRatios,
});

const orange = new Color({
  name: 'orange',
  colorKeys: ['#fdf7ed', '#3e160a', '#f8d7ac', '#923615', '#ef8f35', '#e15627'],
  ratios: lightContrastRatios,
});

const amber = new Color({
  name: 'amber',
  colorKeys: ['#fefaec', '#401b06', '#fae692', '#8b4217', '#f4bb40', '#f09e38'],
  ratios: lightContrastRatios,
});

const emerald = new Color({
  name: 'emerald',
  colorKeys: ['#effcf5', '#b5f1d0', '#5fd096', '#439669', '#1e4d3c', '#347857'],
  ratios: lightContrastRatios,
});

const blue = new Color({
  name: 'blue',
  colorKeys: ['#f0f6fe','#437df6','#223bb1','#2245dd','#c3dafb','#65a0f7','#192352'],
  ratios: lightContrastRatios,
});

const yellow = new Color({
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
  ratios: lightContrastRatios,
});

const slate = new Color({
  name: 'slate',
  colorKeys: ['#f2f5f8', '#cbd5e0', '#64728b', '#48546a', '#1f283a'],
  ratios: lightContrastRatios,
});



const lightBackground = new BackgroundColor({
  name: 'background',
  colorKeys: ['#F8FAFC'],
  ratios: [1],
});

// Light theme: background is white
const lightTheme = new Theme({
  colors: [red, slate],
  backgroundColor: lightBackground,
  lightness: 100,
});

const stops = [
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

console.log('\n=== LIGHT THEME ===');
const lightColors = lightTheme.contrastColors.find(
  (c) => c.name === 'red'
).values;
lightColors.forEach((val, i) => {
  console.log(
    `--color-red-${stops[i]}: ${val.value}  (contrast: ${val.contrast})`
  );
});