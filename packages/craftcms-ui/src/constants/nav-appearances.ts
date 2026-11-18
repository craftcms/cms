export const NavAppearance = {
  List: 'list',
  Tabs: 'tabs',
} as const;

export const navAppearances = Object.values(NavAppearance);

export type NavAppearanceKey = keyof typeof NavAppearance;
export type NavAppearanceValue =
  (typeof NavAppearance)[keyof typeof NavAppearance];
