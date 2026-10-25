export type ReleaseStatus = CraftCms.Cms.Release.Enums.ReleaseStatus;
export type ReleaseSummary = CraftCms.Cms.Release.Data.ReleaseSummaryData;
export type ReleaseReadiness = CraftCms.Cms.Release.Data.ReleaseReadiness;

/**
 * A release's date and time in the system time zone, followed by that zone's
 * abbreviation, as the control panel shows other timestamps.
 */
export function formatReleaseDate(value: string | null): string {
  if (!value) {
    return '';
  }

  const date = new Date(value);
  const timeZone = window.Craft?.timezone;
  const formatted = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone,
  }).format(date);
  // `dateStyle` can't be combined with `timeZoneName`, so it's read separately.
  const zone = new Intl.DateTimeFormat('en-US', {
    timeZone,
    timeZoneName: 'short',
  })
    .formatToParts(date)
    .find((part) => part.type === 'timeZoneName')?.value;

  return zone ? `${formatted} ${zone}` : formatted;
}
