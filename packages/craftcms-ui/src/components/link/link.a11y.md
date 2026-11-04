# `craft-link` accessibility

The link is a native `<a>`. Its details follow the WAI-ARIA APG [Disclosure](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/) pattern: a link icon button before the link opens a `craft-popover` holding the full address and buttons to open and copy it. See `popover.a11y.md` for the panel's own requirements.

**Not for:**

- Showing the details when the link gets focus. A panel at every link while tabbing through a list covers where focus goes next. Use the details button.
- Links in running text, where a button after each link would interrupt reading. Use a plain `<a>`.
- A value that isn't a link, such as a handle or a token. Use `craft-copy-attribute`.

## Requirements

### Semantics

- [x] The link stays a native `<a>` in the light DOM, so it keeps its role, its `href`, middle-click and the browser's "Copy link". Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps the link in the light DOM` in `link.test.ts`.
- [x] The details open from a real button (`craft-button`, `type="button"`). Per WCAG 4.1.2 (Name, Role, Value) and 2.1.1 (Keyboard). Verified by `opens the details from a button` in `link.test.ts`.
- [x] The details button's accessible name is a translated "Link details" followed by the link's text, so the buttons on a page are told apart, and `label` replaces the first part. Per WCAG 2.4.6 (Headings and Labels) and 4.1.2 (Name, Role, Value). Verified by `names the details button after the link` and `takes a label of its own` in `link.test.ts`.
- [x] The panel is named after the kind of link: "Link", "Email" or "Phone". Per WCAG 4.1.2 (Name, Role, Value). Verified by `names the details after the kind of link` and `shows and copies the bare address of %s` in `link.test.ts`.
- [x] The button that opens the address is a link with the original's `href` and `rel` that always opens in a new tab, and its name, "Open in a new tab", says so. Per WCAG 4.1.2 (Name, Role, Value) and 3.2.5 (Change on Request). Verified by `opens the address in a new tab, even when the link opens in place` in `link.test.ts`.
- [x] The copy button is a `craft-button` named with a translated "Copy". Per WCAG 4.1.2 (Name, Role, Value). Verified by `styles the copy button as the open button` in `link.test.ts`.
- [x] The open and copy buttons each show a `craft-tooltip` with their name. It repeats the name the button already has. Per WCAG 1.1.1 (Non-text Content) and 2.5.3 (Label in Name). Verified by `shows a tooltip on each of the panel buttons` in `link.browser.test.ts`.
- [x] The address in the panel is a link to the same place as the original, with its `target` and `rel`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `links the full address to the same place as the original` in `link.test.ts`.
- [x] Copying is announced through a status message, "Copied" or "Copy failed", and the copy button's icon and name change to match. Per WCAG 4.1.3 (Status Messages). Verified by `shows and announces that the address was copied` and `shows and announces that copying failed` in `link.browser.test.ts`.

### Keyboard and focus

- [x] Tab reaches the details button, then the link, matching their order on screen. Per WCAG 2.1.1 (Keyboard), 2.4.3 (Focus Order) and 1.3.2 (Meaningful Sequence). Verified by `reaches the details button, then the link, from the keyboard` in `link.browser.test.ts`.
- [x] Enter on the details button opens the panel without moving focus or acting on anything inside it. Per WCAG 3.2.1 (On Focus) and 2.4.3 (Focus Order). Verified by `opens the details with Enter, keeping focus on the button and copying nothing` in `link.browser.test.ts`.
- [x] With the panel open, Tab moves to the open button, the copy button and the full address, then on to the link. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `moves through the open and copy buttons, then the full address, with Tab` and `moves on to the link after the open panel` in `link.browser.test.ts`.
- [x] The copy button copies the full address. Per WCAG 2.1.1 (Keyboard). Verified by `copies the full address` in `link.browser.test.ts`.
- [x] Escape closes the panel and returns focus to the details button. While a panel button's tooltip shows, the first Escape closes only the tooltip. Per WCAG 2.1.1 (Keyboard) and 1.4.13 (Content on Hover or Focus). Verified by `closes with Escape and returns focus to the details button` in `link.browser.test.ts`.
- [x] Focusing the link doesn't open the details. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)) and 3.2.1 (On Focus). Verified by `reaches the details button, then the link, from the keyboard` in `link.browser.test.ts`.
- [ ] Moving focus out of the panel closes it. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Known gap: `craft-popover` only closes on Escape or an outside click.

### Hover

- [x] Resting the pointer on the link or its details button opens the details after a warm-up, without moving focus, whether or not the link is cut off. A pointer that moves on before the warm-up, and touch, open nothing. Per WCAG 1.4.13 (Content on Hover or Focus) and 2.5.2 (Pointer Cancellation). Verified by `opens the details once the pointer rests on the link for the warm-up`, `opens the details from the pointer resting on the details button too`, `opens the details when the link fits, too`, `leaves the details closed when the pointer moves off before the warm-up` and `ignores touch` in `link.browser.test.ts`.
- [x] Once one link's details have opened, another link's open straight away and replace them, so two panels never overlap. The warm-up applies again after a cool-down. Per WCAG 1.4.13 (Content on Hover or Focus). Verified by `opens other links straight away once warm, closing the last` and `waits out the warm-up again once the cool-down passes` in `link.browser.test.ts`.
- [x] The pointer can move onto hover-opened details without them closing, and they close once it leaves. Per WCAG 1.4.13 (Content on Hover or Focus), hoverable and persistent. Verified by `keeps hover-opened details open while the pointer is on them` and `closes hover-opened details once the pointer leaves` in `link.browser.test.ts`.
- [x] Escape closes hover-opened details without moving the pointer or focus. Per WCAG 1.4.13 (Content on Hover or Focus), dismissible. Verified by `closes hover-opened details with Escape without moving focus` in `link.browser.test.ts`.
- [x] Clicking the details button while hover-opened keeps the details open, rather than closing them. Per WCAG 3.2.2 (On Input). Verified by `keeps hover-opened details open once the details button is clicked` in `link.browser.test.ts`.

### Visual

- [x] A link longer than its container is cut off on one line with an ellipsis, and the full address is in the panel. Per WCAG 1.4.10 (Reflow). Verified by `clips the link to one line` in `link.browser.test.ts` and `shows and copies the full address` in `link.test.ts`.
- [ ] The details button is at least 24×24 CSS px, or has enough space around it. Per WCAG 2.5.8 (Target Size (Minimum)). It uses `craft-button`'s `xsmall` size, whose hit area is padded to the small touch target. Not yet verified.
- [x] The panel's open and copy buttons are at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `gives the panel's buttons at least a 24px target` in `link.browser.test.ts`.
- [ ] The details button and the panel's buttons show a focus indicator at 3:1 against their surroundings, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.
- [x] The details button's icon shows the kind of link: a link, an envelope or a phone. Its name says what it does, so the icon stays decorative to assistive technology. Per WCAG 1.1.1 (Non-text Content). Verified by `marks the details button with the kind of link` and `names the details button after the link` in `link.test.ts`.
- [x] The details button keeps its hover background while the panel is open, alongside the `aria-expanded` the popover sets on it. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `shows the details button as hovered while the panel is open` in `link.browser.test.ts`.
- [x] The details button's icon takes the link color, so it reads as part of the link. Per WCAG 1.3.1 (Info and Relationships). Verified by `colors the details icon as a link` in `link.browser.test.ts`.
- [ ] In forced-colors mode, the details button's icon and the panel's buttons stay visible. Not yet verified.

## Depends on usage

- 2.4.4 (Link Purpose (In Context)): give the `<a>` text that says where it goes, or the address itself. See [Accessibility](./Link.mdx#accessibility).
