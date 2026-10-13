export type ReleaseStatus = CraftCms.Cms.Release.Enums.ReleaseStatus;
export type ReleaseSummary = CraftCms.Cms.Release.Data.ReleaseSummaryData;
export type ReleaseReadiness = CraftCms.Cms.Release.Data.ReleaseReadiness;

export function formatReleaseDate(value: string | null): string {
  if (!value) {
    return '';
  }

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: window.Craft?.timezone,
  }).format(new Date(value));
}
