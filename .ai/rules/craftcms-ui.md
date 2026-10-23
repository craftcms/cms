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
copy its pattern without applying that test first.

## Document a11y requirements for new components and a11y-relevant changes
Create or update a `<component>.a11y.md` file next to a component (see `button/button.a11y.md`, `thumbnail/thumbnail.a11y.md`) whenever you create a component, or add or change behavior that could affect accessibility — even if the change isn't accessibility-motivated. That covers keyboard navigation, focus order, and screen-reader behavior (accessible names, announcements) same as it covers motion, contrast, target size, cognitive load, zoom/reflow, and anything else in `.ai/rules/accessibility.md`'s scope — none of these is the sole trigger, and none is excluded. The file is a `## Requirements` checklist, one requirement per line, each noting what verifies it — a `.test.ts`/`.browser.test.ts` case, or a Storybook `play()` assertion only when that requirement is itself a documented example (see the story-vs-browser-test rule above). Write the checklist first, unchecked, then use it as the spec for the tests/story you add; check an item off only once something actually verifies it. This is in addition to, not instead of, the automated axe-core pass every story already gets — the checklist covers what axe can't catch.

Each requirement states a **common behavior**: something every use of the component relies on, that its interaction pattern or WCAG expects of it, and that a reviewer auditing any component of this kind would check (it has an accessible name, focus moves in on open and back to the invoker on close, Escape closes it). Write it in the present tense, as how the component works. A bug or regression fix is guarded by its regression test alone; the checklist changes only when the fix changes a common behavior. To check: if the item only makes sense to someone who knows the bug's history, it belongs in a test name, not the checklist.

The checklist holds only what the component itself does, so every item can be verified by a test or story in this package. Check an item off only when it cites one: a `.test.ts`/`.browser.test.ts` file under `packages/craftcms-ui/src`, or a story from the component's own stories file that has a `play()` function asserting it. A story that only renders doesn't count. Quote test names exactly. Tests in `resources/js` or `tests/` don't count. Anything that depends on how the component is used goes in the component's `.mdx` page under `## Accessibility`, written as usage guidance. Examples: setting `label`, using a real button as the invoker, saying which trigger characters a field accepts. Don't describe usage in the `.a11y.md`, and don't use the word "consumer" there, except inside a quoted test name. Keep it the other way too: the `.mdx` describes intended use only. When the component falls short of a common behavior ("doesn't reach", "isn't announced", "is ignored"), leave that requirement unchecked and note the known gap in a sentence. Don't describe it in the `.mdx` or give a workaround there, since the fix belongs in the code. When the component can't meet a WCAG criterion on its own, add a plain bullet (no checkbox) under `## Depends on usage` that names the criterion and links the `.mdx` page, e.g. `- 3.3.2 (Labels or Instructions): name the trigger characters. See [Accessibility](./TextExpander.mdx#accessibility).` `a11y-docs.test.ts` enforces all of this.

## Name the component's interaction pattern when one exists
If a documented pattern fits the component — a WAI-ARIA APG pattern, or another well-documented or researched one — name and link it at the top of its `.a11y.md`, followed by a "Not for" list: behaviors that would break the pattern, and the component to use instead (see `tabs/tabs.a11y.md`). Check any new attribute, mode or state against both before adding it. If it contradicts the pattern, use the component that matches the behavior, per "Choosing an interaction pattern" in `.github/instructions/a11y.instructions.md`. `craft-tabs` grew a `collapsible` mode this way and had to be removed. If no documented pattern applies, the requirements checklist alone is enough.

## Build the UI package before consumers
Build `@craftcms/ui` with `vp run build:ui` before building or checking the main Vite application after UI package changes. Run its tests with `vp run test:ui`.

## Don't comment CSS unless it's a hack
Leave CSS uncommented — `.css`/`.scss` files, Lit `css` templates and Vue `<style>` blocks. Only comment a genuine hack, or a workaround for platform or library behaviour a reader couldn't infer, and keep it to one line about the code, not the history behind it.

## Don't comment tests unless the behaviour is odd
Leave tests uncommented; the test name and assertions say what is being checked. Only comment when the behaviour under test is decidedly odd and a reader couldn't infer why the assertion holds, and keep it to one line about the code, not the bug or history that prompted the test.
