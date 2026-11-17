/** Whether the primary pointer is precise, like a mouse rather than touch. */
export function hasMousePointerEvents(): boolean {
  return matchMedia('(pointer:fine)').matches;
}
