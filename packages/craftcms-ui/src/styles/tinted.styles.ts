import {unsafeCSS, type CSSResult} from 'lit';
import {neutralColors} from '@src/constants/colors.data';

const neutralColorList = neutralColors
  .map((color) => `[data-color='${color}']`)
  .join(', ');

/**
 * A `:host()` selector matching a host in a tinted color: one with a
 * `data-color` that isn't one of the {@link neutralColors}. `extra` is
 * appended inside the `:host()`, for further conditions on the host.
 *
 * @example css`${tintedHost(':not([appearance~="plain"])')} { … }`
 */
export function tintedHost(extra = ''): CSSResult {
  return unsafeCSS(`:host([data-color]:not(${neutralColorList})${extra})`);
}
