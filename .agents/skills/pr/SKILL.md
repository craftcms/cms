---
name: pr
description: "Use when writing a PR body."
---

Use this template for writing the PR body:

```markdown
### Description

<diagram, diff-sketch, or tree>

### Evidence

- **Before:** <screenshot/output/failing test run>
  **After:** <screenshot/output/passing test run>

### Related issues

- #1234
```

## Sections

Skip all preambles and keep prose brief.

### Description

Load and follow the `show-me` skill to create the smallest visual that clearly explains the change.

### Evidence

Concrete evidence that the change works. Show a before and after.

Screenshots are S-tier - when the environment is set up for it and the change is visual. For visual control panel changes, load the `feature-screenshots` skill to capture the before and after screenshots.

Execution-based evidence is A-tier. Test results, console output. Show the exact test that now fails and passes, using pseudocode.

### Related issues

Add this section only when there are related issues and link to them. If the branch already contains a Linear issue identifier it does not need to be added, link to the GitHub issue instead.
