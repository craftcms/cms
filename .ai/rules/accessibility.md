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

For the full operational checklist (contrast ratios, forced-colors mode,
reflow, forms, tables, and more) see `.github/instructions/a11y.instructions.md` —
maintained for GitHub Copilot's reviews, and equally applicable here.

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

## Automated checks are necessary, not sufficient

This repo runs axe-core automatically against every Storybook story
(`packages/craftcms-ui`'s `@storybook/addon-a11y`, part of `vp run test:ui`).
A clean run is a real, required gate — but it is not proof of conformance.
Automated tools reliably catch maybe a third to half of real-world WCAG
issues; the rest need a deliberate, manual pass.

A clean automated run is a floor, not a finish line — treat it as one
required check among several, not proof that a change is done.
