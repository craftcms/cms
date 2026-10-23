# `craft-input-date-time` accessibility

Composes a `craft-input-date`, a `craft-input-time` and, with `show-timezone`, a `craft-input` for the time zone, created as its own light-DOM children. Each part keeps the requirements in `input.a11y.md`, `input-date.a11y.md` and `input-time.a11y.md`. The component has no label, help text or group role of its own. Inside `craft-field` the field becomes a `role="group"` named by its label; see `field.a11y.md`.

**Not for:**

- A date alone or a time alone with no paired value to submit: use `craft-input-date` or `craft-input-time`.

## Requirements

### Semantics

- [x] The date and time native inputs each have their own accessible name, "Date" and "Time", so a person moving between them knows which part they're in, including when only one part is shown. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `names the date and time inputs separately` and `a date-only field still names its one input` in `labels.test.ts`.
- [x] The time zone input, when shown, is named "Time Zone". Per WCAG 4.1.2 (Name, Role, Value). Verified by `names the timezone input when it is shown` in `labels.test.ts`.
- [ ] `disabled` and `readonly` reach every part's native input. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] `required` marks the date and time native inputs as required. Per WCAG 3.3.2 (Labels or Instructions) and 4.1.2 (Name, Role, Value). Known gap: it's assigned to a `required` property that neither `craft-input` nor Lion defines, so the native inputs get no `required` or `aria-required` and no `Required` validator.
- [ ] The element named by `described-by` is in each part's native input's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships). Known gap: the attribute is set on each part's `craft-input` host, a generic element where it has no effect, and `craft-input` doesn't forward it to the native input.

### Keyboard and focus

- [x] The component's own inputs come first in DOM order, date then time then time zone, followed by slotted content, even when the slotted content was there first or a part is turned on later. So reading and Tab order put a clear button after the inputs it acts on. Per WCAG 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order). Verified by `keeps its own inputs ahead of slotted content` in `input-date-time.test.ts`.
- [x] With a single part shown, focusing its input draws the standard focus ring around the whole field, clear button included, standing off its border, while a focused clear button keeps its own ring instead. With several parts, each draws its ring inside the shared border. Per WCAG 2.4.7 (Focus Visible). Verified by `rings a lone date or time input around the whole field, standing off its border` in `input-date-time.browser.test.ts`.

### Reflow and zoom

- [ ] The parts wrap onto separate lines rather than overflowing at a 320px viewport. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 1.3.1 (Info and Relationships): wrap the component in `craft-field`, so the parts are grouped and named by the field label. See [Accessibility](./InputDateTime.mdx#accessibility).
- 3.3.2 (Labels or Instructions): when the time zone isn't shown, say which zone the time is in. See [Accessibility](./InputDateTime.mdx#accessibility).
- 2.5.8 (Target Size (Minimum)): a slotted button, such as a clear button, needs an accessible name and a 24×24 CSS px target. See [Accessibility](./InputDateTime.mdx#accessibility).
