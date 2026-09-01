import {colors, type ColorValue} from './colors';

/**
 * The known element statuses.
 *
 * Mirrors the statuses Craft's elements report (entries, users, etc.).
 */
export const Status = {
  Enabled: 'enabled',
  Disabled: 'disabled',
  Live: 'live',
  Pending: 'pending',
  Expired: 'expired',
  Active: 'active',
  Suspended: 'suspended',
  Inactive: 'inactive',
} as const;

export const statuses = Object.values(Status);

export type StatusKey = (typeof Status)[keyof typeof Status];

/**
 * Maps a status name to a known color.
 *
 * Mirrors `\CraftCms\Cms\Shared\Enums\Color::tryFromStatus()` so the front-end
 * resolves the same color as server-rendered status indicators. Unknown
 * statuses fall back to `gray`.
 */
const statusColors: Record<string, ColorValue> = {
  on: 'teal',
  live: 'teal',
  active: 'teal',
  enabled: 'teal',
  turquoise: 'teal',
  off: 'red',
  suspended: 'red',
  expired: 'red',
  warning: 'amber',
  pending: 'orange',
  grey: 'gray',
};

export function statusColor(status: string | null | undefined): ColorValue {
  if (!status) {
    return 'gray';
  }

  const mapped = statusColors[status];
  if (mapped) {
    return mapped;
  }

  // The status may itself be a known color name (mirrors `Color::tryFrom()`).
  if ((colors as string[]).includes(status)) {
    return status as ColorValue;
  }

  return 'gray';
}
