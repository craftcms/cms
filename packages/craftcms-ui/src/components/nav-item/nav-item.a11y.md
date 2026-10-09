# `craft-nav-item` accessibility

## Requirements

- [ ] Source buttons expose their current state on the native button, accept keyboard activation, and retain focus after the source changes. Not yet verified. `announces only the page itself as the page` in `nav-item.test.ts` covers `aria-current` on the inner link.
- [ ] Items belong to their containing list across shadow roots, including nested groups. Not yet verified.
- [ ] Source labels and keyboard focus remain visible in forced colors. Not yet verified.
