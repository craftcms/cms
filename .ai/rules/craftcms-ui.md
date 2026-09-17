---
paths:
  - 'packages/craftcms-ui/**'
---

# Craftcms Ui

## Story vs. `.browser.test.ts` — decide by whether it's a documented example
A Storybook story is for browsable, documented variants of a component's
public API — props/modes/states a consumer would deliberately configure and
might want to see rendered (e.g. `thumbnail.stories.ts`'s `Modes`,
`Presentations`). An internal robustness or regression check — retry
timing, an edge case nobody configures on purpose, "does this bug stay
fixed" — belongs in `<component>.browser.test.ts` instead: same real-Chromium
Playwright execution (the `components-browser` vitest project), same
`expect`/assertion API via `vite-plus/test`, just not shown in the catalog.
Litmus test: would someone browsing Storybook to learn the component's API
benefit from seeing this as a named example? If not, it's a browser test,
not a story — don't reach for the nearest existing `play()`-based story to
copy its pattern without asking that first.

## Document a11y requirements for new components and a11y-relevant changes
Create or update a `<component>.a11y.md` file next to a component (see `button/button.a11y.md`, `thumbnail/thumbnail.a11y.md`) whenever: creating a new component, fixing an accessibility regression, or adding/changing any behavior that could affect accessibility — even if the change isn't accessibility-motivated. That covers keyboard navigation, focus order, and screen-reader behavior (accessible names, announcements) same as it covers motion, contrast, target size, cognitive load, zoom/reflow, and anything else in `.ai/rules/accessibility.md`'s scope — none of these is the sole trigger, and none is excluded. The file is a `## Requirements` checklist, one requirement per line, each noting what verifies it — a `.test.ts`/`.browser.test.ts` case, or a Storybook `play()` assertion only when that requirement is itself a documented example (see the story-vs-browser-test rule above). Write the checklist first, unchecked, then use it as the spec for the tests/story you add; check an item off only once something actually verifies it. This is in addition to, not instead of, the automated axe-core pass every story already gets — the checklist covers what axe can't catch.

## Build the UI package before consumers
Build `@craftcms/ui` with `vp run build:ui` before building or checking the main Vite application after UI package changes. Run its tests with `vp run test:ui`.

## Don't comment CSS unless it's a hack
Leave CSS uncommented — `.css`/`.scss` files, Lit `css` templates and Vue `<style>` blocks. Only comment a genuine hack, or a workaround for platform or library behaviour a reader couldn't infer, and keep it to one line about the code, not the history behind it.

## Don't comment tests unless the behaviour is odd
Leave tests uncommented; the test name and assertions say what is being checked. Only comment when the behaviour under test is decidedly odd and a reader couldn't infer why the assertion holds, and keep it to one line about the code, not the bug or history that prompted the test.
