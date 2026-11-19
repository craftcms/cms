---
name: requesting-accessibility-reviews
description: "Requests an accessibility review on a craftcms/cms pull request by posting one comment for @gcamacho079 that points to what needs her attention. Use when asked to request, ask for, or prepare an accessibility (a11y) review of a PR."
---

# Requesting accessibility reviews

@gcamacho079 is the accessibility reviewer. Post one PR comment that tells her where to look. She's the team's accessibility subject matter expert, so don't explain concepts, don't summarize the PR, and don't list things that are fine.

## Before writing

1. Read `.github/instructions/a11y.instructions.md` and `.ai/rules/accessibility.md`. Only changed files inside their path scopes count: `src/Cp`, `packages/craftcms-ui`, `packages/craftcms-legacy`, `resources/js`, `resources/css` and `resources/templates`. Ignore logic-only changes with no markup, style, focus, keyboard or announcement change.
2. For each changed component or page, find where it's actually used, and check each changed variant separately (placement, appearance, size, color). If only tests use something, or only `src/Cp` enums or PHP components that offer it as an option, it's not on an app screen. Its test path is then Storybook. Link the PR's Storybook preview comment if there is one; otherwise name the story. Never invent steps to reach it in the app.
3. Leave out anything that correctly uses an existing `@craftcms/ui` component or a pattern already used elsewhere in the app: a decorative `aria-hidden` SVG, a visually hidden new-window cue, `craft-button`'s `loading` state, and so on. Listing what's correct is what makes these comments long.

## What to flag

Only these, each backed by a specific line in the diff:

- **Likely bug:** the code clearly breaks a MUST in the instructions, for example a missing name, focus lost when an element is removed, a hand-rolled widget where a component exists, or a color that isn't a token.
- **Needs a decision:** a behavior or design choice the rules don't settle, for example announcements nobody asked for, a visually hidden heading, a control removed in some states.
- **Needs AT testing:** behavior that only a screen reader, keyboard pass, forced colors, zoom/reflow or a contrast check across themes can confirm, especially anything the PR author says they didn't test.

## Format

Hard limit: 200 words outside the collapsed section. With no findings, post only: "No accessibility-relevant changes found. Files checked: <n>."

```markdown
**Accessibility: N items to look at**

**Likely bugs**
- <What's wrong, in one sentence> ([file:line](link)). <Instructions section or WCAG SC, only if sure>.

**Needs a decision**
- <The question, in one sentence> ([file:line](link)).

**Needs AT testing**
- <What to check and with what> — <shortest UI path, e.g. Dashboard → About widget → refresh, or "Storybook: <story>" when step 2 found no app screen>.

<details><summary>Scope</summary>

Files in scope, one line each. Mark variants with no app screen as "Storybook only". Plus anything beyond the 3-per-group limit.

</details>
```

N is the number of bullets, not the number of checks. A bullet can ask for more than one check on the same change.

Skip any empty group. Put the most important item first in each group, with at most 3 per group. Put the rest in the collapsed section.

## Rules

- Never say something passes or is accessible. You only read the code.
- Don't guess. If you can't point to a line, leave it out.
- Cite a section or SC only when you've confirmed it applies.
- No headings beyond the ones above, no tables, no emoji, no closing summary.
