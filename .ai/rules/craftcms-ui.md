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
copy its pattern without applying that test first. Give each thing a
consumer can vary its own story. Describe behavior every instance shares in
a story that already shows it, rather than a story of its own.

Before adding to `<component>.stories.ts`, check why you want the story. If
it's to get a check (axe, a `play()` assertion) or to show a reviewer a case,
it isn't an API story: verify the case in a `.browser.test.ts`, and add it to
the edge-cases stories file only if a reviewer needs to see it. Placing the
component somewhere, such as inside a `craft-field`, in a table cell or
beside other content, to show how it's named or wired up (`aria-labelledby`
pointing at a column heading) isn't an API variant either. Document the attribute in the `.mdx` prose and verify
it in a browser test.

Edge cases a reviewer needs to see — hard inputs, stress content, failure
states, and fallbacks for missing data — go in
`<component>.edge-cases.stories.ts`, titled with the component's own
`<component>.stories.ts` title plus `/Edge Cases` (`Form Controls/Slide
Picker/Edge Cases`, `Components/Card/Edge Cases`) and tagged
`tags: ['!autodocs']`, so they sit inside the component's folder in the
sidebar but stay off its docs page. Name each after the mode it exercises
(`CoverHardImages`), and size its canvas so the whole case is visible.
They're published with the rest of Storybook, so they can be reviewed with
or without a PR.
Share their fixtures with the browser tests from `packages/craftcms-ui/test/`,
so reviewers see what the tests measure. The story shows the case; the
browser test verifies it, so an edge-case story never counts as verification
in a `.a11y.md`.

## Document a11y requirements for new components and a11y-relevant changes
Create or update a `<component>.a11y.md` file next to a component (see `button/button.a11y.md`, `thumbnail/thumbnail.a11y.md`) whenever you create a component, or add or change behavior that could affect accessibility — even if the change isn't accessibility-motivated. That covers keyboard navigation, focus order, and screen-reader behavior (accessible names, announcements) same as it covers motion, contrast, target size, cognitive load, zoom/reflow, and anything else in `.ai/rules/accessibility.md`'s scope — none of these is the sole trigger, and none is excluded. The file is a `## Requirements` checklist, one requirement per line, each noting what verifies it — a `.test.ts`/`.browser.test.ts` case, or a Storybook `play()` assertion only when that requirement is itself a documented example (see the story-vs-browser-test rule above). Write the checklist first, unchecked, then use it as the spec for the tests/story you add; check an item off only once something actually verifies it. This is in addition to, not instead of, the automated axe-core pass every story already gets — the checklist covers what axe can't catch.

Each requirement states a **common behavior**: something every use of the component relies on, that its interaction pattern or WCAG expects of it, and that a reviewer auditing any component of this kind would check (it has an accessible name, focus moves in on open and back to the invoker on close, Escape closes it). Write it in the present tense, as how the component works. A bug or regression fix is guarded by its regression test alone; the checklist changes only when the fix changes a common behavior. To check: if the item only makes sense to someone who knows the bug's history, it belongs in a test name, not the checklist.

The checklist holds only what the component itself does, so every item can be verified by a test or story in this package. Check an item off only when it cites one: a `.test.ts`/`.browser.test.ts` file under `packages/craftcms-ui/src`, or a story from the component's own stories file that has a `play()` function asserting it. A story that only renders doesn't count. Quote test names exactly. The cited test has to meet the "Test oracles" rule in `AGENTS.md`: an independent oracle, inputs that could make it fail, and seen to fail once. Tests in `resources/js` or `tests/` don't count. Anything that depends on how the component is used goes in the component's `.mdx` page under `## Accessibility`, written as usage guidance. Examples: setting `label`, using a real button as the invoker, saying which trigger characters a field accepts. Don't describe usage in the `.a11y.md`, and don't use the word "consumer" there, except inside a quoted test name. Keep it the other way too: the `.mdx` describes intended use only. When the component falls short of a common behavior ("doesn't reach", "isn't announced", "is ignored"), leave that requirement unchecked and note the known gap in a sentence. Don't describe it in the `.mdx` or give a workaround there, since the fix belongs in the code. When the component can't meet a WCAG criterion on its own, add a plain bullet (no checkbox) under `## Depends on usage` that names the criterion and links the `.mdx` page, e.g. `- 3.3.2 (Labels or Instructions): name the trigger characters. See [Accessibility](./TextExpander.mdx#accessibility).` `a11y-docs.test.ts` enforces all of this.

## Name the component's interaction pattern when one exists
If a documented pattern fits the component — a WAI-ARIA APG pattern, or another well-documented or researched one — name and link it at the top of its `.a11y.md`, followed by a "Not for" list: behaviors that would break the pattern, and the component to use instead (see `tabs/tabs.a11y.md`). Check any new attribute, mode or state against both before adding it. If it contradicts the pattern, use the component that matches the behavior, per "Choosing an interaction pattern" in `.github/instructions/a11y.instructions.md`. `craft-tabs` grew a `collapsible` mode this way and had to be removed. If no documented pattern applies, the requirements checklist alone is enough.

## Build form controls on Lion
A new single form control is built on Lion, so `craft-field`, validation and posting work as they do for every other control:

- Extend an existing Craft control when yours is a variation of one, as `craft-select-color` extends `craft-select-rich`.
- Otherwise extend `HasLabel(LionField)` and render the element people interact with into its `input` slot from `get slots()`. `craft-slide-picker` does this with `craft-slide-picker-slider`, the same way `craft-switch` uses `craft-switch-button`.
- When a listbox generates its own options, render them into `_listboxNode`, as `craft-combobox` does.
- Expose the value as `modelValue`, adding a `model-value` attribute when markup needs to set it. Lion's `value` is the string view value.
- To let markup point at a label or description the control doesn't own, such as a table column heading, apply the `HostAriaReferences` mixin, which reads `labelled-by` and `described-by` on the host. Don't read `aria-labelledby` or `aria-describedby` from the host: the host isn't the control, and the attribute names should say so. `craft-combobox` predates this and still reads `aria-labelledby`.

Don't hand-write `addToAriaLabelledBy`/`addToAriaDescribedBy`, don't put the focusable element in a shadow root, and don't nest one form control inside another.

## Build the UI package before consumers
Build `@craftcms/ui` with `vp run build:ui` before building or checking the main Vite application after UI package changes. Run its tests with `vp run test:ui`.

## Don't comment CSS unless it's a hack
Leave CSS uncommented — `.css`/`.scss` files, Lit `css` templates and Vue `<style>` blocks. Only comment a genuine hack, or a workaround for platform or library behaviour a reader couldn't infer, and keep it to one line about the code, not the history behind it.

## Don't comment tests unless the behaviour is odd
Leave tests uncommented; the test name and assertions say what is being checked. Only comment when the behaviour under test is decidedly odd and a reader couldn't infer why the assertion holds, and keep it to one line about the code, not the bug or history that prompted the test.
