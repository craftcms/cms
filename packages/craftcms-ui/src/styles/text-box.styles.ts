import {css} from 'lit';

/**
 * Trims a text label to its cap height and alphabetic baseline, so it centers
 * on its capitals, then pads it back out to a full line so it keeps its
 * height. Use this where the label's height sizes its container, or where the
 * label clips its overflow (the padding keeps descenders visible). Where a
 * container already has a fixed height, `text-box: trim-both cap alphabetic`
 * alone is enough.
 *
 * `text-box-trim` isn't inherited and only applies to block containers, so
 * include this in the rule for the block that holds the text itself.
 */
export const trimmedTextBoxStyles = css`
  @supports (text-box: trim-both cap alphabetic) {
    text-box: trim-both cap alphabetic;
    padding-block: calc((1lh - 1cap) / 2);
  }
`;
