# `craft-input-color` accessibility

Extends `craft-input`; see `input.a11y.md` for label, help text, validation and focus requirements.

The swatch is a native `<input type="color">`, made transparent and laid over a preview of the current color. The hex text field beside it is the primary control.

**Not for:**

- Choosing from a fixed palette, where any other color is wrong: use `craft-select-color`.

## Requirements

### Semantics

- [ ] The swatch's picker is a native `<input type="color">`, so it keeps its native role, value and keyboard behavior. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The picker's accessible name says which field it sets. Per WCAG 4.1.2 (Name, Role, Value) and 2.4.6 (Headings and Labels). Known gap: the picker is always named "Color picker", so two color fields on a page have identically named pickers, and neither name includes the field's label.
- [ ] The color preview behind the picker is decorative and has no role or text, so the color isn't announced twice. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [x] `disabled` disables the native picker as well as the text field. Per WCAG 4.1.2 (Name, Role, Value). Verified by `sets disabled state on the native picker` in `input-color.test.ts`.
- [ ] The text field's accessible name or description says it takes a hex value. Per WCAG 1.3.1 (Info and Relationships). Known gap: the `#` prefix that shows this is hidden from screen readers, and nothing else in the field says it.

### Use of color

- [x] A color chosen with the picker is written into the text field as hex, so the value is always readable as text and not by the swatch alone. Per WCAG 1.4.1 (Use of Color). Verified by `updates the model from the native color picker without a # prefix` in `input-color.test.ts`.
- [x] A value that isn't a valid hex color stays in the text field as typed and clears the preview, rather than showing a color the value doesn't name. Per WCAG 1.4.1 (Use of Color). Verified by `keeps invalid text values and clears the preview` in `input-color.test.ts`.
- [ ] A value that isn't a valid hex color is identified in text in `feedback`. Per WCAG 3.3.1 (Error Identification). Known gap: the field has no built-in hex validator, so an invalid value only empties the swatch, which doesn't say what's wrong.

### Keyboard and focus

- [x] The value can be entered by typing into the text field, with or without a leading `#`, so the picker is never the only way to set it. Per WCAG 2.1.1 (Keyboard). Verified by `strips a leading # from typed values` in `input-color.test.ts`.
- [ ] When the browser doesn't support `<input type="color">`, the picker is left out and the text field still works on its own. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Tab order is the picker, then the text field. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [ ] A focused picker shows the global focus ring around the swatch at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Visual

- [ ] The swatch is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. It's sized to the input height.
- [ ] The swatch's edge reaches 3:1 against the surface whatever the color, including white and near-white on a light surface and black on a dark one. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. The edge is a 1px inset `--c-color-border-translucent` shadow, which is likely below 3:1.
- [ ] The `#` prefix (`--c-text-quiet`) reaches 4.5:1 against the input fill, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] In forced colors mode the swatch's edge and focus ring stay visible. Not yet verified. The preview's background color and its box-shadow edge are both dropped in forced colors, so the swatch shows no color and no outline. The value stays readable in the text field.

## Depends on usage

- 1.4.1 (Use of Color): name what a preset means in help text, since presets are offered by value alone. See [Accessibility](./InputColor.mdx#accessibility).
