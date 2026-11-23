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
  pending: 'sky',
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

/**
 * A status dot's shape, which it takes on when the user's "Use shapes to
 * represent statuses" preference is on.
 */
export type StatusShape = 'circle' | 'square' | 'triangle' | 'diamond';

/**
 * Maps a status name to the shape its dot takes with that preference on:
 * a triangle for pending, a square for expired, suspended and off, a
 * diamond for “all” (an empty status), and a circle for everything else.
 *
 * Mirrors `\CraftCms\Cms\Cp\Html\StatusHtml::statusShape()`.
 */
export function statusShape(status: string | null | undefined): StatusShape {
  switch (status) {
    case '':
    case 'all':
      return 'diamond';
    case 'pending':
      return 'triangle';
    case 'off':
    case 'suspended':
    case 'expired':
      return 'square';
    default:
      return 'circle';
  }
}
