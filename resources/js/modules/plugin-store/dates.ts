/** The API sends `Y-m-d H:i:s`, which not every browser will parse as-is. */
export function apiDate(value: string): Date {
  return new Date(value.replace(' ', 'T'));
}
