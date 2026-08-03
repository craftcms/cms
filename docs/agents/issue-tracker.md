# Issue tracker: GitHub

Issues and PRDs for this repo live in the private GitHub repository `riasvdv/field-layout-issues`. Use the `gh` CLI for all operations.

Always pass `--repo riasvdv/field-layout-issues`. Never infer the issue repository from this worktree's Git remotes.

## Conventions

- **Create an issue**: `gh issue create --repo riasvdv/field-layout-issues --title "..." --body "..."`. Use a heredoc for multi-line bodies.
- **Read an issue**: `gh issue view <number> --repo riasvdv/field-layout-issues --comments`.
- **List issues**: `gh issue list --repo riasvdv/field-layout-issues --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'` with appropriate `--label` and `--state` filters.
- **Comment on an issue**: `gh issue comment <number> --repo riasvdv/field-layout-issues --body "..."`
- **Apply or remove labels**: `gh issue edit <number> --repo riasvdv/field-layout-issues --add-label "..."` or `--remove-label "..."`
- **Close**: `gh issue close <number> --repo riasvdv/field-layout-issues --comment "..."`

## Pull requests as a triage surface

**PRs as a request surface: no.** _(Set to `yes` if this repo treats external PRs as feature requests; `/triage` reads this flag.)_

When set to `yes`, PRs run through the same labels and states as issues, using the `gh pr` equivalents:

- **Read a PR**: `gh pr view <number> --repo riasvdv/field-layout-issues --comments` and `gh pr diff <number> --repo riasvdv/field-layout-issues`.
- **List external PRs for triage**: `gh pr list --repo riasvdv/field-layout-issues --state open --json number,title,body,labels,author,authorAssociation,comments`, then keep only `authorAssociation` values of `CONTRIBUTOR`, `FIRST_TIME_CONTRIBUTOR`, or `NONE`.
- **Comment, label, or close**: use `gh pr comment`, `gh pr edit`, or `gh pr close` with `--repo riasvdv/field-layout-issues`.

GitHub shares one number space across issues and PRs. Resolve a bare `#42` with `gh pr view 42 --repo riasvdv/field-layout-issues`, then fall back to `gh issue view 42 --repo riasvdv/field-layout-issues`.

## When a skill says “publish to the issue tracker”

Create an issue in `riasvdv/field-layout-issues`.

## When a skill says “fetch the relevant ticket”

Run `gh issue view <number> --repo riasvdv/field-layout-issues --comments`.

## Wayfinding operations

Used by `/wayfinder`. The **map** is a single issue with **child** issues as tickets.

- **Map**: an issue labelled `wayfinder:map`, holding the Notes, Decisions-so-far, and Fog body.
- **Child ticket**: an issue linked to the map as a GitHub sub-issue. Where sub-issues are unavailable, add the child to a task list in the map body and put `Part of #<map>` at the top of the child body. Use a `wayfinder:<type>` label: `research`, `prototype`, `grilling`, or `task`.
- **Blocking**: use GitHub's native issue dependencies. Where dependencies are unavailable, use a `Blocked by: #<n>, #<n>` line at the top of the child body.
- **Frontier query**: list the map's open children, drop tickets with an open blocker or assignee, and select the first ticket in map order.
- **Claim**: `gh issue edit <number> --repo riasvdv/field-layout-issues --add-assignee @me`.
- **Resolve**: comment with the answer, close the ticket, and append a private context pointer to the map's Decisions-so-far.
