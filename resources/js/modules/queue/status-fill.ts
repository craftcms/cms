import {Color, type ColorValue} from '@craftcms/ui';
import {JobStatus} from './types';

/** The badge color for a job status. */
export function jobStatusFill(status: number): ColorValue {
  switch (status) {
    case JobStatus.Reserved:
    case JobStatus.Done:
      return Color.Success;
    case JobStatus.Failed:
      return Color.Danger;
    case JobStatus.Delayed:
      return Color.Warning;
    default:
      return Color.Gray;
  }
}
