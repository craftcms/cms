export const Volume = {
  Quiet: 'quiet',
  Normal: 'normal',
  Loud: 'loud',
} as const;

export const volumes = Object.values(Volume);

export type VolumeKey = keyof typeof Volume;
export type VolumeValue = (typeof Volume)[keyof typeof Volume];
