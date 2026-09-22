# `craft-tabs` accessibility

## Requirements

- [x] Given a height, the panel region scrolls its own content rather than
      overflowing the host, and that scrolling is operable from the keyboard:
      each panel carries `tabindex="0"` (the WAI-ARIA APG tabs pattern), so the
      scroll container is reachable by tabbing and its content by arrow/page
      keys, with no pointer required. Per WCAG 2.1.1 (Keyboard). Verified by
      `tabs.browser.test.ts`, which focuses a panel, presses PageDown, and
      asserts the region scrolled.
- [x] The tab strip stays in place while the panels scroll, so the tablist
      never scrolls out of reach of someone reading the far end of a long
      panel. Per WCAG 2.4.3 (Focus Order). Verified by the same case, which
      asserts the strip hasn't moved once the panel has scrolled.
- [x] Under a host with no height of its own the panels grow instead of
      scrolling, so nothing is clipped or lost when the page is reflowed or
      zoomed. Per WCAG 1.4.10 (Reflow). Verified by `tabs.browser.test.ts`'s
      unbounded-host case.
