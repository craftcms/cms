# `craft-missing-component` accessibility

## Requirements

- [x] The `error` renders as visible text, so the failure isn't conveyed by color or an icon alone. Per WCAG 1.4.1 (Use of Color) and 1.3.1 (Info and Relationships). Verified by `renders the missing component error` in `missing-component.test.ts`.
- [ ] The message isn't announced as an alert when it's present at page load. Per WCAG 4.1.3 (Status Messages). Known gap: the message is always `role="alert"`, even when empty, so a page with several missing components can fire several alerts as it renders.
- [x] The plugin name renders as visible text in a heading. Per WCAG 1.3.1 (Info and Relationships). Verified by `renders plugin details and slotted actions` in `missing-component.test.ts`.
- [ ] The plugin heading fits the page outline. Per WCAG 1.3.1 (Info and Relationships). Known gap: it's always an `<h3>`.
- [ ] The `icon` slot's wrapper is `aria-hidden`, so the plugin icon is decorative. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] The error text meets 4.5:1 against the component's background, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
