/** The API sends `Y-m-d H:i:s`, which not every browser will parse as-is. */
export function formatDate(value: string): string {
  return new Date(value.replace(' ', 'T')).toLocaleDateString(
    window.Craft?.formattingLocale,
    {year: 'numeric', month: 'long', day: 'numeric'}
  );
}
