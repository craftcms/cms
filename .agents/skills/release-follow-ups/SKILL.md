---
name: release-follow-ups
description: Find the craftcms/cms issues, discussions, and community PRs that one or more Craft releases addressed, then draft and pre-fill "it's out now" follow-up comments in the browser in batches. Use when asked to follow up on, announce, or comment on items fixed or added in a release (such as "follow up on everything from 5.12.0–5.12.2"), or to find what a release resolved that still needs a comment or closing.
---

# Release follow-ups

After a release, let the people who opened issues, discussions, and PRs know that their fix or feature has shipped. Do it in two parts:

- **A. Find the items** that the releases resolved and that still need a follow-up comment or closing.
- **B. Draft and pre-fill** the comments in batches for a human to review and post.

## A. Find the items

Run the script with the release tags, oldest first or in any order:

```sh
python3 .agents/skills/release-follow-ups/scripts/find-follow-ups.py 5.12.0 5.12.1 5.12.2
```

It needs `gh`, signed in as someone who can list craftcms org members. Add `--include-skipped` to see what it filtered out and why, or `--json` for machine-readable output (it includes discussion node IDs and nested-reply targets).

What it does:

1. Reads each release's notes and `published_at`. Each `craftcms/cms/(issues|pull|discussions)/<n>` reference belongs to the first version that mentions it. A discussion linked on the same line as a PR, such as `([#8983](…discussions/8983), [#20274](…pull/20274))`, counts as resolved by that PR.
2. Loads team members from `orgs/craftcms/members`. Someone also counts as a team member when GitHub reports their association as `MEMBER` or `OWNER`, because members can hide their org membership.
3. Fetches every number with GraphQL, all comments paginated. It tries `issueOrPullRequest` first, then `discussion`, and flattens discussion replies while keeping each top-level comment's node ID.
4. For each PR in the notes, adds the items it resolves: `closingIssuesReferences` plus `#<n>` or URL references in its body. A merged PR by someone outside the team becomes a "community PR" item itself.
5. Skips:
   - numbers that don't exist (hex colors like `#123456`, "UTS #39", `#0`)
   - items opened by a team member, including companion or stacked 5.x/6.x PRs
   - unmerged PRs
   - items a team member already commented on after that version's `published_at`

   A team comment from before the release ("Fixed for the next release", "coming in 5.12") doesn't count. It only tells you how to word the follow-up.
6. Prints one list grouped by version, then by type (open discussions, closed discussions, issues, community PRs). Each item shows its fixing PRs, whether the team has thanked the author, whether the fix was announced or linked before the release, the last team comment, and a nested-reply target for discussions.

### Judge the flags yourself

The script can't decide these, so it flags them. Read the thread and the PR for every flagged item before drafting:

- **Only referenced in a PR body.** Body references are noisy. Drop items the PR only calls related or uses as background ("Related to #…", "inspired by #…", "as of #…", a "Considered, but left alone" section) unless the PR actually resolves them. Example: #20149 listed #8035 and #9830 as related, but its own profiling showed it didn't fix them, so neither got a comment. A "Related discussions" heading doesn't rule an item out, though: #10010 sat under one and was resolved.
- **"Not exactly" / partial.** Skip items where a team member said the change is "not exactly what you're asking for" (#13982, #18370, #19320), and requests the change only partly covers (#9021).
- **Also in an earlier release.** Skip items whose fix already shipped before, like #19657 in 5.11.3.
- **Non-team comments since the release.** Read them. If the poster already shows they know it shipped, skip it. On #19761 they thanked us for the new setting and asked for something new, so a "now out" comment would have been noise.
- **Reported against 6.x.** Wait until a 6.0 release includes the fix too, then name both releases. Check with `git log <6.x tag> --oneline --grep=<pr>`, because 6.x release notes don't always list fixes merged up from 5.x.
- **Author wrote the fixing PR.** Thank them for the PR (see the wording rules below).
- **Needs closing.** Still-open items need closing after the comment, so list them for the human.

Before describing a feature, check the code or the PR so the wording is accurate. Still keep the comment short: "the ability to query user groups via GraphQL", not a field-by-field rundown.

## B. Draft and pre-fill in batches

### Workflow

1. **Batches of 3.** For each item, read the latest comments with `gh` (it's faster and more reliable than scraping pages), then open it in its own tab in the Claude tab group.
2. **Pre-fill, never post.** Put the draft in the right comment box. For a nested discussion reply, open the reply box under the specific comment first. The human reviews, edits, and submits every comment and does all closing and answer-marking. Never submit, post, edit, close, or mark answers yourself, through the browser or `gh`.
3. **Report.** Say which tabs are ready, quote each draft, list the items you skipped and why, and say which ones need closing. Then wait.
4. **Compare.** When the human says the batch is done, read what they actually posted with `gh` and compare it with your drafts. Say in one line what changed, apply it to every later draft, and add a lasting lesson to this skill. If they didn't post on an item, treat it as skipped and work out why.
5. **Reuse tabs.** If the previous batch's tabs are still open, navigate them to the next batch instead of opening new ones.

Look ahead with the script's output so each batch report can mention what's next and anything that needs a decision.

### Browser mechanics

- **Never type into a GitHub page.** If focus isn't in a textarea, keystrokes trigger GitHub keyboard shortcuts: `s` opens search, `l` opens labels, `a` opens assignees, `g s` goes to Security. Set the value with JavaScript instead, and check the result:

  ```js
  const t = [...document.querySelectorAll('textarea')].find(x => /Markdown|answer/i.test(x.placeholder)) || document.getElementById('new_comment_field');
  t.scrollIntoView({block: 'center'}); t.focus();
  Object.getOwnPropertyDescriptor(HTMLTextAreaElement.prototype, 'value').set.call(t, BODY);
  t.dispatchEvent(new Event('input', {bubbles: true}));
  t.value;
  ```

  On PRs, the main comment box is `#new_comment_field` and its placeholder is blank.
- **Nested discussion replies.** Use `find` to get the "Write a reply" button under the specific comment, click it, then confirm `document.activeElement` is a `TEXTAREA` before setting its value. Take a screenshot to confirm the box sits under the right comment.
- **If a stray keystroke lands,** press Escape to close any menu without selecting anything, then confirm nothing changed with `gh` (labels, assignees, `updatedAt`, timeline).
- **Closing any tab in the Claude tab group ends the browser session.** Warn the human. If the group disappears, run `tabs_context_mcp` with `createIfEmpty: true` and reopen the batch.

If you notice a typo or slip in something the human posted, point it out in your next report. They'll fix it.

## Wording rules

Keep comments to one or two short sentences. Mention only the part of the PR that matters to that item. Prefer "the ability to <do X>" over listing specifics.

### Versions

- **Bug reports:** use the exact version: "Craft 5.12.0", "Craft 5.12.1".
- **Feature discussions** from a minor release: use the minor version, "Craft 5.12". Use the patch version for discussions fixed in a patch release: "Craft 5.12.1 is out now with that fix."
- **When a 6.0 release also has it,** name both: "Craft 5.12.2 and 6.0.0-beta.2 are out with that fix."

### Bug reports

Nobody on the team has thanked the reporter yet:

> Thanks for reporting that! Craft 5.12.1 is out with a fix.

| Draft | Posted |
| --- | --- |
| Thanks for the report. Craft 5.12.0 is out with a fix. | Thanks for reporting that! Craft 5.12.0 is out with a fix. |

"Thanks for pointing that out." also works for things that aren't quite bugs.

A team member already thanked them, or any earlier comment mentions the fix in any form, even just "I raised a PR for this":

> Craft 5.12.1 is out with that fix. Thanks again!

| Draft | Posted |
| --- | --- |
| Craft 5.12.0 is out with a fix. Thanks again! | Craft 5.12.0 is out with that fix. Thanks again! |

The thread's earlier comment was "Hi, thanks for reporting! I raised a PR for this." For the human, that already counted as mentioning the fix.

The reporter wrote the fix themselves in their own PR:

> Craft 5.12.1 is out with your fix. Thanks for the PR!

### Feature discussions

**If a team member already left a top-level comment pointing to the PR or saying it's coming,** post a nested reply under that comment. Refer back to what that comment described instead of describing it again:

| Draft | Posted |
| --- | --- |
| Craft 5.12 is out now with language-aware casing filters and slug generation. | Craft 5.12 is out now with those filter changes. |

**If that comment didn't say what was added** ("Added for Craft 5.12! #20212"), name the feature:

> Craft 5.12 is out now with the ability to paste files into Assets fields from the clipboard.

**Use the requester's own names for settings, and name every release that shipped it:**

| Draft | Posted |
| --- | --- |
| Craft 5.12 is out now with the ability to set default tips and warnings for custom fields. | Craft 5.12 and 6.0.0-beta.1 are out with the new “Default Tip” and “Default Warning” settings. |

**With no team comment yet,** leave a top-level comment:

> Craft 5.12 added the ability to query sites via GraphQL 🎉 (#20055)

**For a "potential fix" the team had asked people to test,** state it plainly once it ships: "This is now resolved in Craft 5.12 (#19366)."

### Multi-point feedback

When a discussion raises several separate points, write a top-level, numbered reply. Open with a short thanks, map each point to what shipped with its PR, and end with a clear decision on anything that didn't ship. Don't leave it open-ended:

| Draft | Posted |
| --- | --- |
| Thanks for the detailed writeup! Craft 5.12.1 addressed items 1–4: … | Thanks for the suggestions! Craft 5.12.1 addressed items 1–4: … |
| Nothing has changed for item 5 (handles in place of uids) yet. | We decided against using handles instead of UUIDs (item 5) for now. |

Draft the decision line as a placeholder for the human to fill in, because you won't know it.

### Community PRs

Not yet thanked:

| Draft | Posted |
| --- | --- |
| Thanks for the PR! Craft 5.12.0 is out with it. | Thanks! Craft 5.12.0 is out with this. |

Already thanked:

| Draft | Posted |
| --- | --- |
| Craft 5.12.0 is out with this. Thanks again! | Craft 5.12.0 is out with that. Thanks again! |

### @mentions

Leave them out by default. Add one only when you're answering someone other than the item's author whose report hasn't had a reply. On #16340, another user reported a new console bug in the old thread and nobody had answered:

| Draft | Posted |
| --- | --- |
| Thanks for pointing that out! Craft 5.12.1 is out with a fix, so console-generated password reset and email verification URLs now point to the control panel for users with control panel access. | @suprim-simple Thanks for pointing that out. Craft 5.12.1 is out with a fix, so console-generated password reset and email verification URLs now point to the control panel for users with control panel access. |

On #19881, the human dropped the mention instead. That reply followed directly on an exchange with the same person, who had already been answered:

| Draft | Posted |
| --- | --- |
| @MoritzLost Craft 5.12.0 is out now with draft support for addresses. | Craft 5.12.0 is out now with draft support for addresses. |

That reply also didn't end with "Thanks again!", because the commenter wasn't the reporter.

## Skip, partial, and closing decisions from past runs

- **Already followed up after the release:** skip, even if someone else on the team posted it (#12202, #17101, #20308).
- **Alternatives count as resolved.** #20183 asked to paste an image "from the clipboard or provide its URL". Only clipboard pasting shipped, and that counted as resolved, because the URL was an alternative suggestion rather than a second requirement.
- **Partial resolution of separate requests** (#9021, #20412) isn't "clearly resolved". Ask the human. For #20412 they wanted the itemized reply above, then closed it.
- **A post-release comment from the poster** that shows they know it shipped means skip (#19761).
- **Old issue, new bug in the thread:** comment only about the new release's change. #16340 was fixed differently in 5.6, so the 5.12.1 comment covered only the console URL change.

**Closing:**
- The human closes open discussions as **resolved** and marks the release comment as the answer, moving the answer mark from an older comment if there is one (#14631).
- Issues were usually already closed as completed. Report any that are still open so the human can close them.
- Never close anything yourself.
