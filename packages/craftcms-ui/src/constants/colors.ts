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

  // Semantic
  Neutral: 'slate',
  Accent: 'red',
  Success: 'emerald',
  Warning: 'orange',
  Danger: 'red',
  Info: 'blue',

  // Status
  Pending: 'orange',
  Off: 'red',
  Suspended: 'red',
  Expired: 'red',
  Disabled: 'gray',
  Inactive: 'gray',
  On: 'emerald',
} as const;

export const colors = Object.values(Color);

export type ColorKey = keyof typeof Color;
export type ColorValue = (typeof Color)[keyof typeof Color];
