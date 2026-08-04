// Creates or updates a single PR comment that links each changed component to
// its rendered story in the deployed Storybook previews.
//
// Invoked from .github/workflows/storybook.yml via actions/github-script:
//
//   const run = require('./.github/scripts/storybook-preview-comment.cjs')
//   await run({github, context})
//
// The deploy results/URLs are passed in through the environment (CP_RESULT,
// CP_URL, RESOURCES_RESULT, RESOURCES_URL) so untrusted values never get
// interpolated into the workflow's inline script.

const MARKER = '<!-- storybook-preview-comment -->';

// Directory portion of a story's importPath, normalized for comparison against
// the repo-relative paths returned by the GitHub API.
const dirOf = (p) => p.replace(/^\.\//, '').split('/').slice(0, -1).join('/');

// Storybook publishes an index of every story at `<preview>/index.json`. We use
// it to turn a changed file into a deep link to its rendered component.
async function storyIndex(url) {
  try {
    const res = await fetch(`${url}/index.json`);
    if (!res.ok) return [];
    return Object.values((await res.json()).entries || {});
  } catch {
    return [];
  }
}

// Build the markdown section for one preview: an "open Storybook" link plus a
// list of the components touched by this PR, each deep-linked into the preview.
async function sectionFor(preview, changed) {
  if (preview.result !== 'success' || !preview.url) {
    return `**${preview.name}** — ⚠️ deploy ${preview.result}, no preview available`;
  }

  const entries = await storyIndex(preview.url);

  // A component "changed" if any changed file lives in the same directory as one
  // of this Storybook's story entries. Collapse the several entries per story
  // file down to one link per component, preferring the Docs page when present.
  //
  // Scope the changed-file matching to the relevant subtree so similarly-named
  // directories elsewhere in the repo don't create false positives.
  const baseDir =
    preview.name === '@craftcms/ui'
      ? 'packages/craftcms-ui'
      : preview.name === 'resources/js'
        ? 'resources/js'
        : null;

  const changedInScope = baseDir
    ? changed
        .filter((file) => file.startsWith(`${baseDir}/`))
        .map((file) => file.slice(baseDir.length + 1))
    : changed;

  const byImport = new Map();
  for (const entry of entries) {
    if (!entry?.importPath) continue;
    const dir = dirOf(entry.importPath);
    if (!dir || !changedInScope.some((file) => file.startsWith(`${dir}/`)))
      continue;
    const current = byImport.get(entry.importPath);
    if (!current || (entry.type === 'docs' && current.type !== 'docs')) {
      byImport.set(entry.importPath, entry);
    }
  }

  const links = [...byImport.values()]
    .sort((a, b) => a.title.localeCompare(b.title))
    .map((entry) => {
      const kind = entry.type === 'docs' ? 'docs' : 'story';
      return `- [${entry.title}](${preview.url}/?path=/${kind}/${entry.id})`;
    });

  const heading = `**${preview.name}** — [open Storybook](${preview.url})`;
  return links.length
    ? `${heading}\n\nChanged components:\n${links.join('\n')}`
    : `${heading}\n\n_No changed components detected in this Storybook._`;
}

module.exports = async ({github, context}) => {
  const previews = [
    {
      name: '@craftcms/ui',
      result: process.env.CP_RESULT,
      url: process.env.CP_URL,
    },
    {
      name: 'resources/js',
      result: process.env.RESOURCES_RESULT,
      url: process.env.RESOURCES_URL,
    },
  ];

  // Every file changed in this PR (excluding deletions, which no longer have a
  // rendered story to point at).
  const changed = (
    await github.paginate(github.rest.pulls.listFiles, {
      owner: context.repo.owner,
      repo: context.repo.repo,
      pull_number: context.issue.number,
      per_page: 100,
    })
  )
    .filter((file) => file.status !== 'removed')
    .map((file) => file.filename);

  const sections = [];
  for (const preview of previews) {
    sections.push(await sectionFor(preview, changed));
  }

  const body = `${MARKER}\n### 📚 Storybook previews\n\n${sections.join('\n\n')}`;

  const {data: comments} = await github.rest.issues.listComments({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.number,
  });
  const existing = comments.find((comment) => comment.body.includes(MARKER));

  if (existing) {
    await github.rest.issues.updateComment({
      owner: context.repo.owner,
      repo: context.repo.repo,
      comment_id: existing.id,
      body,
    });
  } else {
    await github.rest.issues.createComment({
      owner: context.repo.owner,
      repo: context.repo.repo,
      issue_number: context.issue.number,
      body,
    });
  }
};
