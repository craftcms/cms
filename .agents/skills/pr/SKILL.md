---
name: pr
description: "Use when writing or updating a PR title or PR body."
---

Keep the PR title under 72 characters and in sync with the PR body.

Use this template for writing the PR body:

```markdown
### Description

- <why the change is needed>
- <what it changes>

### Evidence

- **Before:** <screenshot/output/failing test run>
  **After:** <screenshot/output/passing test run>

### Related issues

- #1234
```

## Sections

Skip all preambles and keep prose concise and quickly scannable by a human:

- use hierarchy within each section (subheadings, bulleted lists, nesting) to avoid dense blocks of text
- use GitHub footnotes (`[^1]`) to include detail without sacrificing readability
- put test output, console output, and other literal text in code blocks, and identifiers in inline code

### Description

Start with a few bullets on why the change is needed and what it changes. Don't jump straight to how.

Leave the how to the code. Mention an implementation detail only to call it out for the reviewer, and move longer reasoning into footnotes.

### Evidence

Concrete evidence that the change works. Show a before and after.

Screenshots are S-tier - when the environment is set up for it and the change is visual. For visual control panel changes, load the `feature-screenshots` skill to capture the before and after screenshots.

Execution-based evidence is A-tier. Test results, console output. Show the exact test that now fails and passes, using pseudocode.

### Related issues

Add this section only when there are related issues and link to them. If the branch already contains a Linear issue identifier it does not need to be added, link to the GitHub issue instead.
