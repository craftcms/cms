export const AsyncStates = {
  Idle: 'idle',
  Loading: 'loading',
  Success: 'success',
  Error: 'error',
} as const;

export type AsyncState = (typeof AsyncStates)[keyof typeof AsyncStates];
