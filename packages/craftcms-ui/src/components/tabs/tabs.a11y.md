# `craft-tabs` accessibility

Follows the WAI-ARIA APG [Tabs with Automatic Activation](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/examples/tabs-automatic/) example.

## Requirements

### Semantics

- [x] Tabs carry `role="tab"`, `aria-selected`, and `aria-controls`; panels carry `role="tabpanel"` and an `aria-labelledby` naming their tab. Per WCAG 4.1.2 (Name, Role, Value). Verified in external-panel mode by `wires the tab/tabpanel contract across the two halves` in `tabs.test.ts`, and in slotted mode by the `Default` story.
- [x] The tablist has an accessible name, set with `label` and applied to the `role="tablist"` element as `aria-label`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `names the tablist from label` in `tabs.test.ts`.
- [x] `aria-orientation` matches the axis the strip runs along: `horizontal` for the block placements, `vertical` for the inline ones. Per WCAG 4.1.2 (Name, Role, Value). Verified by `derives the tablist orientation from the axis` in `tabs.test.ts`.
- [ ] Exactly one tab is selected at all times. The strip doesn't behave like a disclosure: clicking the selected tab or pressing Escape doesn't collapse its panel. Per WCAG 4.1.2 (Name, Role, Value), since a collapsed strip exposes tabs with no selected panel. Not yet implemented; `collapsible` contradicts it.

### Keyboard and focus

- [x] Roving tabindex: only the selected tab is in the Tab order. Per WCAG 2.1.1 (Keyboard). Verified by `keeps a roving tabindex` in `tabs.test.ts` and the `Default` story.
- [x] Tab moves from the selected tab into its panel (or the panel's first focusable element), and Shift+Tab from there returns to the selected tab. Per WCAG 2.4.3 (Focus Order). Verified by `tabs from the selected tab into its panel and back` in `tabs.browser.test.ts`.
- [x] Left and Right arrow keys (Up and Down on the inline placements) select the previous or next tab, wrapping at both ends. Per WCAG 2.1.1 (Keyboard). Verified by `navigates with the arrow keys, wrapping at both ends` in `tabs.test.ts` and the `Default` story.
- [x] Home and End select the first and last tabs. Per WCAG 2.1.1 (Keyboard). Verified by `jumps to the ends with Home and End` in `tabs.test.ts`.
- [x] Focus follows the selection: the tab an arrow, Home, or End key selects also receives focus. Per WCAG 2.4.3 (Focus Order). Verified by `moves focus with the selection` in `tabs.browser.test.ts`, and by the arrow and Home/End cases in `tabs.test.ts` for external-panel mode.
- [x] Navigation skips disabled tabs and tabs collapsed into the overflow menu. Per WCAG 2.1.1 (Keyboard). Verified by `skips disabled tabs when navigating, and refuses to select one` and `arrows past collapsed tabs` in `tabs.test.ts`, and the `Disabled` story.
- [ ] Selecting a tab shows its panel without a noticeable delay, as automatic activation requires. A panel that loads its content shows a loading state straight away rather than holding the switch. Not yet verified.
- [ ] The tablist comes directly before the panels it controls in the DOM, so reading order and focus order run from the tablist into the selected panel. Per WCAG 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order). Slotted mode puts the tablist first; in external-panel mode the consumer places the panels. Not yet verified.
- [x] A keyboard-focused tab shows a solid ring at least 2px wide, at 3:1 against the surface in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Verified by `rings a keyboard-focused tab at 3:1 against the surface` in `tabs.browser.test.ts`.
- [x] A focused panel's ring is drawn inside the panel, so the scrolling panel region doesn't clip it. The inset applies only to slotted panels, which sit in the strip's own scroll region. External panels keep the default outset ring, and a consumer that puts them in a scroll container insets the ring there, as `DetailsTabs.vue` does. Per WCAG 2.4.7 (Focus Visible). Verified by `keeps a focused panel’s ring inside the scrolling panel region` in `tabs.browser.test.ts`.

### Visual

- [x] A selected text tab is marked by a bar on the edge facing the panels, plus a heavier weight. The bar reaches 3:1 against the surface in the light and dark themes, and unselected tabs have no bar, so the difference is shape as well as color. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Verified by the `marks the selected text tab …` cases in `tabs.browser.test.ts`, on the block and inline placements.
- [x] A selected icon-only tab, which gets nothing from the weight change, is marked by the same bar at the same 3:1. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Verified by the `marks the selected icon tab …` cases in `tabs.browser.test.ts`, on the block and inline placements.
- [x] The selected bar and the tab and panel focus rings stay visible in forced colors mode. The bar uses the `Highlight` system color there; an ordinary color would be forced to the canvas color and disappear. Verified manually with Playwright's `forced-colors: active` emulation against the `Default`, `IconToolbar`, and `ExternalPanels` stories. There's no automated test, since the browser tests can't emulate forced colors.
- [x] Every tab is at least 24×24 CSS px, or a 24px circle centered on it clears every other tab, at each `size` on the block and inline placements. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by the `gives every … tab a 24px target, or room around it` cases in `tabs.browser.test.ts`.

### Overflow

- [x] Tabs that don't fit the strip get `hidden`, leaving the accessibility tree, and stay reachable through the "More tabs" menu. Selecting one from the menu brings it back into the strip, selects it, and moves focus to it. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by the `Overflow` story.

### Scrolling and reflow

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
