export const Color = {
  Red: 'red',
  Orange: 'orange',
  Amber: 'amber',
  Yellow: 'yellow',
  Lime: 'lime',
  Green: 'green',
  Emerald: 'emerald',
  Teal: 'teal',
  Cyan: 'cyan',
  Sky: 'sky',
  Blue: 'blue',
  Indigo: 'indigo',
  Violet: 'violet',
  Purple: 'purple',
  Fuchsia: 'fuchsia',
  Pink: 'pink',
  Rose: 'rose',
  White: 'white',
  Gray: 'gray',
  Black: 'black',
} as const;

export const colors = Object.values(Color);

export type ColorKey = (typeof Color)[keyof typeof Color];
export type ColorValue = typeof colors;
