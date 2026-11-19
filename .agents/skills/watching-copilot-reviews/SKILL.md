---
name: watching-copilot-reviews
description: "Checks for and reads GitHub Copilot's review on a craftcms/cms pull request. Use when waiting for, watching for, or checking a Copilot review, or when deciding whether Copilot has responded."
---

# Watching Copilot reviews

Copilot's pull request review can arrive as a formal review, or only as ordinary comments on the conversation tab. When waiting for or checking a Copilot review, look at all three places:

- Reviews: `gh api repos/craftcms/cms/pulls/<number>/reviews`
- Inline review comments: `gh api repos/craftcms/cms/pulls/<number>/comments`
- Conversation comments: `gh api repos/craftcms/cms/issues/<number>/comments`

Don't conclude Copilot hasn't responded from reviews alone.

When asked to watch for a Copilot review, schedule checks of all three until it arrives. Don't rely on a GitHub event subscription alone; it doesn't report every Copilot response.

Read each Copilot review's full body. Findings can appear only there, such as under "previously missed issues", even when the summary says there are no open issues. The summary line can also raise concerns, such as "Needs a closer look" with no open issues; investigate each one like a finding.
