# `craft-breadcrumb-item` accessibility

## Requirements

- [x] Linked crumbs (`href` set) are distinguished from plain-text crumbs by more than color alone — a persistent underline, removed on hover. Verified by `breadcrumb-item.browser.test.ts`.
- [x] A crumb without `href` renders as a `<span>`, never a focusable/interactive element. Verified by `breadcrumb-item.test.ts`.
