# Domain Docs

This repository uses a single domain context, covering Craft CMS core and its workspace packages.

## Before exploring, read these

- **`CONTEXT.md`** at the repo root.
- **`docs/adr/`** for decisions affecting the area being changed.

If these files do not exist, proceed silently. The `/domain-modeling` skill creates them when domain terms or architectural decisions are resolved.

## File structure

```text
/
├── CONTEXT.md
├── docs/adr/
└── src/
```

## Use the glossary’s vocabulary

When output names a domain concept—in an issue title, refactor proposal, hypothesis, or test name—use the term defined in `CONTEXT.md`.

If the concept is absent, reconsider whether the project uses another term or note the gap for `/domain-modeling`.

## Flag ADR conflicts

If proposed work contradicts an existing ADR, surface the conflict rather than silently overriding it.
