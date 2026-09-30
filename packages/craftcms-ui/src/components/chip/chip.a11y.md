# `craft-chip` accessibility

## Requirements

- [x] Custom `prefix` content (a selection checkbox, a badge) doesn't hide the chip's thumbnail or status indicator, so the thumbnail's alternative text and the status indicator's label stay in the accessibility tree. Verified by `chip.browser.test.ts`'s "shows the thumbnail and status alongside custom prefix content".
