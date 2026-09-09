export const Edition = {
  Solo: 0,
  Team: 1,
  Pro: 2,
  Enterprise: 3,
} as const;

export interface SystemData {
  name: string | null;
  edition: (typeof Edition)[keyof typeof Edition];
  timeZone: string;
}
