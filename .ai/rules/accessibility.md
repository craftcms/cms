---
paths:
  - 'src/Cp/**'
  - 'packages/craftcms-ui/**'
  - 'packages/craftcms-legacy/**'
  - 'resources/js/**'
  - 'resources/templates/**'
---

# Accessibility

All UI in this codebase — PHP view components, Twig templates, Vue
components, and the Lit web components in `packages/craftcms-ui` alike — must
conform to **WCAG 2.2, Level AA**. This is a hard requirement, not a
best-effort goal: don't ship a UI change without having actually checked it
against this, the same way you wouldn't ship one without running its tests.

## Read the full checklist before planning or editing UI

Before you plan or make any UI change in these paths, open
`.github/instructions/a11y.instructions.md` and read it in full, then apply
every section relevant to the change: semantics, keyboard and focus, links
and buttons, icons, contrast, forced colors, reflow, forms, tables, and the
final verification checklist. Don't stop at this file or at the sections
whose titles look relevant. It's maintained for GitHub Copilot's reviews and
applies equally here.

## Check the standard itself, don't rely on memory alone

WCAG 2.2 is newer than most training data's center of gravity, which skews
toward the older, more widely-documented 2.0/2.1. If you're unsure whether
something satisfies a specific criterion, fetch the authoritative reference
rather than guessing from recall:

- Quick reference (filterable, practical): https://www.w3.org/WAI/WCAG22/quickref/
- Full normative spec: https://www.w3.org/TR/WCAG22/

Criteria genuinely new in 2.2 (i.e. not just "WCAG" as you may already know
it) that are easy to miss because they postdate what most training emphasizes:
**2.4.11 Focus Not Obscured**, **2.5.7 Dragging Movements**, **2.5.8 Target
Size (Minimum)** — interactive targets need at least 24×24 CSS px, or enough
spacing around a smaller one — **3.2.6 Consistent Help**, **3.3.7 Redundant
Entry**, and **3.3.8 Accessible Authentication (Minimum)**.

## Global skip links sit on top of the page chrome

Focused global skip links use `--c-layer-skip-link`. Keep every persistent piece of page chrome (header bar,
sidebars, sticky bars) below that layer, and only let modal layers
(`--c-layer-shade` and up) cover them. Leave other skip links without a
`z-index`. When you change a `z-index` in CP chrome, run
`ScreenSkipLinks.browser.test.ts`. To check that something can actually be
seen, use `expectUnobscured()` rather than `toBeVisible()`, which passes for
covered elements.

## Automated checks are necessary, not sufficient

This repo runs axe-core automatically against every Storybook story
(`packages/craftcms-ui`'s `@storybook/addon-a11y`, part of `vp run test:ui`).
A clean run is a real, required gate — but it is not proof of conformance.
Automated tools reliably catch maybe a third to half of real-world WCAG
issues; the rest need a deliberate, manual pass.

Done when: axe is clean, the component's `.a11y.md` items are each verified
by a test, and you've reported a keyboard and forced-colors check of the
changed UI.

## Write docs in US English
Use US English spellings in JSDoc (component JSDoc feeds the custom elements manifest and Storybook API tables), `<component>.a11y.md` checklists, `.mdx` pages, story names and descriptions, comments, test names, and local variable names: color, behavior, center, labeled, canceling, gray, -ize/-ization. Keep names you don't own as they are: the `aria-labelledby` attribute, Lion's `addToAriaLabelledBy()`, Inertia's `cancelled` visit flag, and exported APIs like `HttpCancelledError`.
