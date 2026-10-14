# `craft-empty` accessibility

## Requirements

### Semantics

- [x] The `label` renders as visible paragraph text, so the message is read in document order. Per WCAG 1.3.1 (Info and Relationships). Verified by `renders the label` in `empty.test.ts`.
- [x] The `icon` renders as a `craft-icon` with no `label`, so it's `aria-hidden` and doesn't repeat the message. Per WCAG 1.1.1 (Non-text Content). Verified by `renders an icon when one is named` in `empty.test.ts` and `is aria-hidden without a label` in `icon.test.ts`.
- [ ] The message is a paragraph, not a heading, and the component offers no heading level. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [ ] Without a `label` and with nothing slotted into `content`, the component renders no empty paragraph. Per WCAG 1.3.1 (Info and Relationships). Known gap: it renders an empty `<p>`.
- [ ] The component sets no role and no live region, so an empty state present at load isn't announced. Per WCAG 4.1.3 (Status Messages). Not yet verified.

### Visual

- [ ] The message meets 4.5:1 against the surrounding surface, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified. The component inherits the surrounding text color.

### Reflow and zoom

- [ ] The message wraps within 60ch and stays readable without horizontal scrolling at a 320px viewport and at 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified.

## Depends on usage

- 1.1.1 (Non-text Content): hide slotted `graphic` artwork that repeats the message, or label it when it says more. See [Accessibility](./Empty.mdx#accessibility).
- 1.3.1 (Info and Relationships): slot a heading into `content` when the empty state stands in for a section's content. See [Accessibility](./Empty.mdx#accessibility).
- 4.1.3 (Status Messages): announce an empty state that appears in response to a search or filter. See [Accessibility](./Empty.mdx#accessibility).
- 2.4.6 (Headings and Labels): name the action in the default slot for what it does. See [Accessibility](./Empty.mdx#accessibility).
