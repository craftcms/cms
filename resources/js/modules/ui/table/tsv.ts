/**
 * Splits tab-separated text into rows of cells, the way spreadsheets copy
 * them: a cell wrapped in double quotes can contain tabs, line breaks, and
 * doubled quotes, and a final line break doesn't start another row.
 */
export function parseTsv(text: string): string[][] {
  const input = text.replace(/\r\n?/g, '\n').replace(/\n$/, '');
  const cell = /(?:"((?:[^"]|"")*)"|([^\t\n]*))(\t|\n|$)/y;
  const rows: string[][] = [[]];
  let match: RegExpExecArray | null;

  while ((match = cell.exec(input))) {
    rows.at(-1)!.push(match[1]?.replaceAll('""', '"') ?? match[2]!);
    if (match[3] === '') break;
    if (match[3] === '\n') rows.push([]);
  }

  return rows;
}
