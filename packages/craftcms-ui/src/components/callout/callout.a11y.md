# `craft-callout` accessibility

**Not for:**

- A message the person must acknowledge or answer before continuing. Use `craft-dialog` instead.

## Requirements

### Semantics

- [ ] The callout sets no role and no live region, so a callout present when the page loads isn't announced as an alert. Per WCAG 4.1.3 (Status Messages). Not yet verified.
- [ ] A callout inserted after load is announced. Per WCAG 4.1.3 (Status Messages). Known gap: the component has no option to make itself a `status` or `alert` region.
- [x] The default icon renders as a `craft-icon` with no `label`, so it's `aria-hidden` and adds nothing to what the text says. Per WCAG 1.1.1 (Non-text Content). Verified by `falls back to the variant's icon` in `callout.test.ts` and `is aria-hidden without a label` in `icon.test.ts`.
- [ ] The variant isn't exposed to assistive technology: the icon that marks it is hidden, and no text names it. Per WCAG 1.3.1 (Info and Relationships). Known gap.
- [ ] The title is bold text, not a heading, so a callout adds no level to the page outline. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [ ] The `title` property also sets the host's native `title` attribute, which browsers can show as a tooltip or expose as a description that repeats the visible title. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Visual

- [x] Links in the title and body take the callout's text color, so they're underlined until hovered. Per WCAG 1.4.1 (Use of Color). Verified by `gives links its text color and an underline until hovered, in every appearance` in `callout-link.browser.test.ts`.
- [x] Links in the `action` slot keep their own styling, so button-styled links aren't underlined. Verified by `leaves links in the action slot to their own styling` in `callout-link.browser.test.ts`.
- [ ] Each non-`neutral` variant supplies a distinct icon shape, so the variant isn't conveyed by color alone. Per WCAG 1.4.1 (Use of Color). `falls back to the variant's icon` and `re-resolves the icon when the variant changes` in `callout.test.ts` cover `warning` and `success` only. Not yet verified for `info` and `danger`.
- [ ] Text and links meet 4.5:1 against the callout background for every variant and appearance, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] In forced-colors mode, the box edge and the icon stay visible. Not yet verified. The `fill` and `plain` appearances have a transparent border, so their box may disappear.

### Reflow and zoom

- [ ] The icon, title, body and action stay readable without horizontal scrolling at a 320px viewport and at 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified. The grid keeps the action beside the body rather than wrapping it below.

## Depends on usage

- 4.1.3 (Status Messages): announce a callout that appears in response to something the person did. See [Accessibility](./Callout.mdx#accessibility).
- 1.4.1 (Use of Color): say in the text what a `variant` means. See [Accessibility](./Callout.mdx#accessibility).
- 1.1.1 (Non-text Content): label slotted `icon` artwork that says something the text doesn't. See [Accessibility](./Callout.mdx#accessibility).
- 1.3.1 (Info and Relationships): slot a real heading into `title` when the callout starts a section. See [Accessibility](./Callout.mdx#accessibility).
