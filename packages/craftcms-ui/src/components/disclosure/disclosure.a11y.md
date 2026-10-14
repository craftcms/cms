# `craft-disclosure` accessibility

Follows the WAI-ARIA APG [Disclosure (Show/Hide)](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/) pattern. Several stacked disclosures, each trigger inside a heading, make an APG [Accordion](https://www.w3.org/WAI/ARIA/apg/patterns/accordion/).

**Not for** (use another component instead):

- Switching between views where one is always showing: use `craft-tabs`.
- Content that blocks the rest of the page until it's dismissed: use `craft-dialog`.
- A floating panel anchored over the page content rather than pushing it down: use `craft-popover`.
- A list of actions or commands to choose from: use `craft-action-menu`.

More than one panel open at once, a trigger that closes its own panel when clicked again, and a trigger that lives apart from its content are all disclosure behaviors, so they belong here rather than in `craft-tabs`.

## Requirements

### Semantics

- [x] In external-target mode, `aria-expanded` on the wrapped button reflects the state: `true` by default, `false` when `state="collapsed"`, and it flips on each click. Per WCAG 4.1.2 (Name, Role, Value). Verified by `defaults to expanded`, `honors a collapsed state set on the host`, `collapses the target it controls` and `expands again` in `disclosure.test.ts`, and by the `Collapsed` and `Expanded` stories.
- [x] In slotted mode, `aria-expanded` on the invoker reflects `opened` and flips on click. Per WCAG 4.1.2 (Name, Role, Value). Verified for the default invoker by the `DefaultInvoker` story. An element passed in `slot="invoker"` isn't covered.
- [ ] The trigger the component manages is a real button: in external-target mode it only picks up a `button[type="button"]`, and the default invoker is a `craft-button` (`role="button"`). An element passed in `slot="invoker"` isn't checked. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] In slotted mode the invoker gets an `aria-controls` that names the content, which gets a generated `id`. Per WCAG 1.3.1 (Info and Relationships). Not yet verified. In external-target mode the component logs an error when the button's `aria-controls` names no element.
- [ ] In slotted mode, collapsed content gets `display: none`, so it leaves the accessibility tree and the Tab order. Per WCAG 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order). No test asserts it. In external-target mode the component only sets `data-state="collapsed"` on the target.
- [ ] Known gap: the component has no heading support for the Accordion pattern. In slotted mode the invoker must be a direct child with `slot="invoker"`, so wrapping the button in a heading puts `aria-expanded` and the click listener on the heading instead. The `Accordion` story uses no headings. Per WCAG 1.3.1 (Info and Relationships).

### Keyboard and focus

- [ ] Enter and Space toggle the disclosure. Per WCAG 2.1.1 (Keyboard). This comes from the native button or `craft-button`, but no test or story presses a key; every existing check uses a click.
- [ ] Toggling leaves focus on the trigger, and in slotted mode the expanded content follows the trigger in the Tab order. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [ ] The default invoker shows a visible focus indicator that isn't hidden by other content. Per WCAG 2.4.7 (Focus Visible) and 2.4.11 (Focus Not Obscured (Minimum)). It inherits `craft-button`'s ring (see `button/button.a11y.md`). Not yet verified for the disclosure.

### Visual

- [ ] The trigger shows its state visually as well as through `aria-expanded`. Per WCAG 1.3.1 (Info and Relationships). Known gap: the default invoker's `chevron-down` icon doesn't change between states, so the only visual cue is whether the content is showing.
- [ ] The default invoker is at least 24×24 CSS px, or has room around it. Per WCAG 2.5.8 (Target Size (Minimum)). Covered by `craft-button`'s sizing. Not yet verified for the disclosure.
- [ ] The state icon and the trigger's focus ring stay visible in forced colors mode. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.

### Motion

- [ ] Expanding and collapsing don't animate, so there's no motion to reduce. Per WCAG 2.3.3 (Animation from Interactions). This holds because Lion's `_showAnimation` and `_hideAnimation` are empty and `craft-disclosure` doesn't override them; nothing guards it. An animation added later needs a `prefers-reduced-motion` fallback.

## Depends on usage

- 4.1.2 (Name, Role, Value): use a real button as the `slot="invoker"` element. See [Accessibility](./Disclosure.mdx#accessibility).
- 2.4.6 (Headings and Labels): label the trigger with what it reveals. See [Accessibility](./Disclosure.mdx#accessibility).
- 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order): in external-target mode, hide the collapsed target with `display: none` or `hidden`, and place it right after the trigger, or move focus to it on expand and back to the trigger on collapse when it can't be. See [Accessibility](./Disclosure.mdx#accessibility).
- 2.4.7 (Focus Visible) and 2.5.8 (Target Size (Minimum)): in external-target mode, give the button a visible focus indicator and a 24×24 CSS px target. See [Accessibility](./Disclosure.mdx#accessibility).
