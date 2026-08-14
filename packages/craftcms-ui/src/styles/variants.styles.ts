/**
 * The variant-to-token mapping, kept at its long-standing import path.
 *
 * The rules themselves are generated into `colorable.styles.ts` from
 * `constants/colors.data.ts` — the same source and the same script
 * (`scripts/generate-colors.js`) that writes `shared/colorable.css` for the
 * document. Hand-maintaining this file is what let the two drift apart.
 *
 * Reach for {@link paletteStyles} instead when a component paints with
 * arbitrary colors rather than the semantic variants.
 */
export {variantStyles as default, paletteStyles} from './colorable.styles.js';
