---
paths:
  - 'packages/craftcms-ui/**'
---

# Craftcms Ui

## Document a11y requirements for new components and a11y-relevant changes
Create or update a `<component>.a11y.md` file next to a component (see `button/button.a11y.md`, `thumbnail/thumbnail.a11y.md`) whenever: creating a new component, fixing an accessibility regression, or adding/changing any behavior that could affect accessibility — even if the change isn't accessibility-motivated. That covers keyboard navigation, focus order, and screen-reader behavior (accessible names, announcements) same as it covers motion, contrast, target size, cognitive load, zoom/reflow, and anything else in `.ai/rules/accessibility.md`'s scope — none of these is the sole trigger, and none is excluded. The file is a `## Requirements` checklist, one requirement per line, each noting what test or story verifies it (a `.test.ts` case, or a Storybook `play()` assertion). Write the checklist first, unchecked, then use it as the spec for the tests/story you add; check an item off only once something actually verifies it. This is in addition to, not instead of, the automated axe-core pass every story already gets — the checklist covers what axe can't catch.

## Build the UI package before consumers
Build `@craftcms/ui` with `vp run build:ui` before building or checking the main Vite application after UI package changes. Run its tests with `vp run test:ui`.

## Don't comment CSS unless it's a hack
Leave CSS uncommented — `.css`/`.scss` files, Lit `css` templates and Vue `<style>` blocks. Only comment a genuine hack, or a workaround for platform or library behaviour a reader couldn't infer, and keep it to one line about the code, not the history behind it.

## Don't comment tests unless the behaviour is odd
Leave tests uncommented; the test name and assertions say what is being checked. Only comment when the behaviour under test is decidedly odd and a reader couldn't infer why the assertion holds, and keep it to one line about the code, not the bug or history that prompted the test.
