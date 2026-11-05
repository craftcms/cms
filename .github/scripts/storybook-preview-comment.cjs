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

  // A component "changed" if a changed file belongs to one of this Storybook's
  // story entries. In a Storybook with a directory per component, that's any
  // file in the entry's directory. Otherwise it's a file beside the entry that
  // shares its name, such as `Table.vue` or `Table.test.ts` for
  // `Table.stories.ts`, so other components in the same directory don't match.
  // Collapse its entries down to one link per
  // component, preferring the Docs page when present. Group by title rather than
  // importPath: a component's Docs page comes from its `.mdx` file, and its
  // stories from its `.stories.ts` file.
  //
  // Each Storybook's importPaths are relative to the directory holding its
  // `.storybook` config, so prefix them with that to compare against the
  // repo-relative paths of the changed files.
  const toRepoPath = (dir) => (preview.root ? `${preview.root}/${dir}` : dir);

  const byTitle = new Map();
  for (const entry of entries) {
    if (!entry?.importPath) continue;
    const dir = dirOf(entry.importPath);
    if (!dir) continue;
    const repoDir = toRepoPath(dir);
    const component = entry.importPath.split('/').pop().split('.')[0];
    const matches = preview.directoryPerComponent
      ? (file) => file.startsWith(`${repoDir}/`)
      : (file) =>
          dirOf(file) === repoDir &&
          file.split('/').pop().startsWith(`${component}.`);
    if (!changed.some(matches)) continue;
    const current = byTitle.get(entry.title);
    if (!current || (entry.type === 'docs' && current.type !== 'docs')) {
      byTitle.set(entry.title, entry);
    }
  }

  const links = [...byTitle.values()]
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
      root: 'packages/craftcms-ui',
      directoryPerComponent: true,
      result: process.env.CP_RESULT,
      url: process.env.CP_URL,
    },
    {
      name: 'resources/js',
      root: '',
      directoryPerComponent: false,
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
