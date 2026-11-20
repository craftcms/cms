# `craft-metadata-list` accessibility

## Requirements

### Semantics

- [x] The list renders in the light DOM, so its `<dl>`, `<dt>` and `<dd>` keep their native semantics and each term stays associated with its description. Per WCAG 1.3.1 (Info and Relationships). Verified by `keeps its description list in the light DOM` in `metadata-list.test.ts`.
- [x] Each label sits beside or above its own value in every layout, never apart from it. Per WCAG 1.3.1 (Info and Relationships). Verified by `puts each label beside its value inline, however narrow`, `puts each label above its value when stacked, however wide` and `follows its own width in auto` in `metadata-list.browser.test.ts`.

### Visual

- [ ] The `row` and `auto` layouts reflow without horizontal scrolling at 320 CSS px wide. Per WCAG 1.4.10 (Reflow). Not yet verified in a browser.
- [ ] Labels meet text contrast at their small size. Per WCAG 1.4.3 (Contrast (Minimum)). They use the default text color; not yet measured.

## Depends on usage

- 1.3.1 (Info and Relationships): wrap a real `<dl>`, with each `<dt>` and its `<dd>` in a `<div>`. See [Accessibility](./MetadataList.mdx#accessibility).
