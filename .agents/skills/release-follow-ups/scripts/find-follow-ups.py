#!/usr/bin/env python3
"""
List the craftcms/cms issues, discussions, and community PRs that a set of
releases addressed and that still need a "now out" follow-up comment.

Usage:
    python3 find-follow-ups.py 5.12.0 5.12.1 5.12.2
    python3 find-follow-ups.py 5.12.2 --json > items.json

Requires `gh`, signed in as someone who can read craftcms org members.

The script gathers facts and applies the mechanical filters (opened by a team
member, team comment since the release, merged-by-team companion PRs, numbers
that don't exist). Judgment calls stay with whoever reads the output: whether
the release clearly resolved the item, whether a body reference is only
"related", and whether the fix already shipped earlier. The output flags those.
"""

import argparse
import json
import re
import subprocess
import sys
from concurrent.futures import ThreadPoolExecutor

REPO = "craftcms/cms"
OWNER, NAME = REPO.split("/")
ORG = "craftcms"

REF_URL = re.compile(r"craftcms/cms/(issues|pull|discussions)/(\d+)")
REF_HASH = re.compile(r"(?<![\w&/])#(\d+)\b")
RELATED_WORDS = re.compile(
    r"(related|relates|inspired|as of|see also|similar|follow[- ]?up|background|previously|regression from|introduced in|part of|companion|stacked|alongside|supersedes|replaces)",
    re.I,
)
RESOLVE_WORDS = re.compile(r"\b(fix(es|ed)?|close[sd]?|resolve[sd]?|implement(s|ed)?|address(es|ed)?)\b", re.I)
NOT_EXACTLY = re.compile(r"not (exactly|quite)|partially|partly|doesn.t (fully|completely)", re.I)
ANNOUNCED = re.compile(r"\b(fixed|added|resolved|implemented|PR.?d|raised a PR|pull request|coming in|for (the )?next release|for craft \d)", re.I)
THANKED = re.compile(r"\bthank", re.I)

COMMENT_FIELDS = "author { login } authorAssociation createdAt body url"


def gh(args, allow_errors=False):
    proc = subprocess.run(["gh", *args], capture_output=True, text=True)
    if proc.returncode != 0 and not allow_errors:
        sys.exit(f"gh {' '.join(args[:3])}… failed: {proc.stderr.strip()}")
    return proc.stdout


def graphql(query, **variables):
    args = ["api", "graphql", "-f", f"query={query}"]
    for key, value in variables.items():
        args += ["-F" if isinstance(value, int) else "-f", f"{key}={value}"]
    out = gh(args, allow_errors=True)
    try:
        return json.loads(out)
    except json.JSONDecodeError:
        return {"data": None, "errors": [{"message": out.strip() or "no response"}]}


# ---------------------------------------------------------------------------
# Releases

def fetch_releases(versions, lookback):
    """Return ({version: release}, [older releases]) from the REST releases list."""
    wanted = set(versions)
    found = {}
    older = []
    page = 1
    while page <= 10:
        batch = json.loads(gh(["api", f"repos/{REPO}/releases?per_page=100&page={page}"]))
        if not batch:
            break
        for rel in batch:
            tag = rel["tag_name"]
            if tag in wanted:
                found[tag] = {"version": tag, "publishedAt": rel["published_at"], "body": rel["body"] or ""}
        if len(found) == len(wanted):
            earliest = min(r["publishedAt"] for r in found.values())
            for rel in batch:
                if rel["published_at"] and rel["published_at"] < earliest and rel["tag_name"] not in wanted:
                    older.append({"version": rel["tag_name"], "publishedAt": rel["published_at"], "body": rel["body"] or ""})
            if len(older) >= lookback:
                break
        page += 1
    missing = wanted - found.keys()
    if missing:
        sys.exit(f"Release(s) not found: {', '.join(sorted(missing))}")
    older.sort(key=lambda r: r["publishedAt"], reverse=True)
    return found, older[:lookback]


def note_refs(body):
    """Yield (kind, number, line) for each craftcms/cms reference in release notes."""
    for line in body.splitlines():
        for kind, num in REF_URL.findall(line):
            yield kind, int(num), line.strip()


# ---------------------------------------------------------------------------
# Team

def team_members():
    out = gh(["api", f"orgs/{ORG}/members", "--paginate", "-q", ".[].login"])
    return {login.strip().lower() for login in out.splitlines() if login.strip()}


def is_team(login, association, members):
    return (login or "").lower() in members or association in ("MEMBER", "OWNER")


# ---------------------------------------------------------------------------
# Fetching items

ISSUE_OR_PR = f"""
query($owner:String!, $name:String!, $number:Int!, $before:String) {{
  repository(owner:$owner, name:$name) {{
    issueOrPullRequest(number:$number) {{
      __typename
      ... on Issue {{
        number title url state stateReason author {{ login }} authorAssociation
        comments(last:100, before:$before) {{ pageInfo {{ hasPreviousPage startCursor }} nodes {{ {COMMENT_FIELDS} }} }}
      }}
      ... on PullRequest {{
        number title url state merged body author {{ login }} authorAssociation
        closingIssuesReferences(first:20) {{ nodes {{ number repository {{ nameWithOwner }} }} }}
        comments(last:100, before:$before) {{ pageInfo {{ hasPreviousPage startCursor }} nodes {{ {COMMENT_FIELDS} }} }}
      }}
    }}
  }}
}}"""

DISCUSSION = f"""
query($owner:String!, $name:String!, $number:Int!, $before:String) {{
  repository(owner:$owner, name:$name) {{
    discussion(number:$number) {{
      id number title url closed category {{ name }} author {{ login }} authorAssociation
      answer {{ id }}
      comments(last:100, before:$before) {{
        pageInfo {{ hasPreviousPage startCursor }}
        nodes {{
          id {COMMENT_FIELDS}
          replies(last:100) {{ pageInfo {{ hasPreviousPage }} nodes {{ {COMMENT_FIELDS} }} }}
        }}
      }}
    }}
  }}
}}"""


def fetch_item(number):
    """Fetch an issue, PR, or discussion with all of its comments. Returns None if it doesn't exist."""
    def paged(query, key):
        nodes, before, head = [], None, None
        while True:
            vars_ = {"owner": OWNER, "name": NAME, "number": number}
            if before:
                vars_["before"] = before
            res = graphql(query, **vars_)
            obj = ((res.get("data") or {}).get("repository") or {}).get(key)
            if obj is None:
                return None
            head = head or obj
            conn = obj["comments"]
            nodes = conn["nodes"] + nodes
            if not conn["pageInfo"]["hasPreviousPage"]:
                break
            before = conn["pageInfo"]["startCursor"]
        head["comments"] = nodes
        return head

    item = paged(ISSUE_OR_PR, "issueOrPullRequest")
    if item:
        item["kind"] = "pr" if item["__typename"] == "PullRequest" else "issue"
        return item
    item = paged(DISCUSSION, "discussion")
    if item:
        item["kind"] = "discussion"
        flat = []
        for c in item["comments"]:
            c["topLevelId"] = c["id"]
            c["isReply"] = False
            flat.append(c)
            if c["replies"]["pageInfo"]["hasPreviousPage"]:
                item.setdefault("warnings", []).append("a thread has more than 100 replies; older ones are missing")
            for r in c["replies"]["nodes"]:
                r["topLevelId"] = c["id"]
                r["isReply"] = True
                flat.append(r)
        item["comments"] = flat
        return item
    return None


def body_refs(pr):
    """References in a PR body, each with its sentence and the Markdown heading above it."""
    refs = []
    heading = ""
    for line in (pr.get("body") or "").splitlines():
        if re.match(r"^#{1,6}\s", line):
            heading = line.lstrip("#").strip()
            continue
        for sentence in re.split(r"(?<=[.!?])\s+", line):
            nums = {int(n) for _, n in REF_URL.findall(sentence)} | {int(n) for n in REF_HASH.findall(sentence)}
            for n in nums:
                context = sentence.strip()[:240]
                refs.append((n, f"[{heading}] {context}" if heading else context))
    return refs


# ---------------------------------------------------------------------------
# Main

def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("versions", nargs="+", help="release tags, e.g. 5.12.0 5.12.1")
    parser.add_argument("--json", action="store_true", help="print JSON instead of Markdown")
    parser.add_argument("--lookback", type=int, default=30, help="older releases to scan for earlier mentions (default 30)")
    parser.add_argument("--include-skipped", action="store_true", help="also list skipped items with reasons")
    args = parser.parse_args()

    releases, older = fetch_releases(args.versions, args.lookback)
    ordered = sorted(releases.values(), key=lambda r: r["publishedAt"])
    members = team_members()

    # number -> {version, fixing PRs, sources}
    targets = {}
    note_prs = {}  # PR number -> version

    def add_target(n, version, via, fixing_pr=None, context=None):
        t = targets.setdefault(n, {"number": n, "version": version, "fixingPrs": set(), "via": [], "contexts": []})
        if fixing_pr:
            t["fixingPrs"].add(fixing_pr)
        t["via"].append(via)
        if context:
            t["contexts"].append(context)

    for rel in ordered:
        for kind, n, line in note_refs(rel["body"]):
            if n in targets or n in note_prs:
                continue  # belongs to the first version that mentions it
            line_prs = [int(m) for k, m in REF_URL.findall(line) if k == "pull"]
            if kind == "pull":
                note_prs[n] = rel["version"]
            else:
                add_target(n, rel["version"], "release notes", None, line)
                for p in line_prs:
                    targets[n]["fixingPrs"].add(p)
        # same line: discussion + PR, e.g. ([#8983](…discussions/8983), [#20274](…pull/20274))

    # Fetch the PRs from the notes
    with ThreadPoolExecutor(8) as pool:
        pr_items = dict(zip(note_prs, pool.map(fetch_item, note_prs)))

    for n, pr in pr_items.items():
        version = note_prs[n]
        if not pr or pr["kind"] != "pr":
            continue
        pr_by_team = is_team(pr["author"]["login"] if pr["author"] else "", pr["authorAssociation"], members)
        if not pr_by_team and pr.get("merged"):
            add_target(n, version, "community PR")
        for ref in pr["closingIssuesReferences"]["nodes"]:
            if ref["repository"]["nameWithOwner"] == REPO and ref["number"] not in note_prs:
                add_target(ref["number"], version, f"closed by #{n}", n)
        for ref_n, sentence in body_refs(pr):
            if ref_n == n or ref_n in note_prs:
                continue
            if ref_n in targets and targets[ref_n]["version"] != version:
                continue
            add_target(ref_n, targets.get(ref_n, {}).get("version", version), f"body of #{n}", n, sentence)

    # Fetch everything else
    pending = [n for n in targets if n not in pr_items]
    with ThreadPoolExecutor(8) as pool:
        fetched = dict(zip(pending, pool.map(fetch_item, pending)))
    fetched.update({n: pr_items[n] for n in targets if n in pr_items})

    results, skipped = [], []
    rel_by_version = {r["version"]: r for r in ordered}

    for n, t in sorted(targets.items()):
        item = fetched.get(n)
        reasons, flags = [], []
        if item is None:
            skipped.append({"number": n, "version": t["version"], "reason": "not an issue, PR, or discussion (hex color, standard number, etc.)"})
            continue

        author = (item.get("author") or {}).get("login") or "ghost"
        published = rel_by_version[t["version"]]["publishedAt"]
        team_author = is_team(author, item.get("authorAssociation"), members)
        fixing = sorted(t["fixingPrs"] - {n})

        only_body = all(v.startswith("body of") for v in t["via"])
        if item["kind"] == "pr":
            if team_author:
                reasons.append("PR by a team member (companion or stacked PR)")
            elif not item.get("merged"):
                reasons.append("PR wasn't merged")
        elif team_author:
            reasons.append(f"opened by team member {author}")

        team_comments = [c for c in item["comments"] if is_team((c.get("author") or {}).get("login"), c.get("authorAssociation"), members)]
        after = [c for c in team_comments if c["createdAt"] >= published]
        if after:
            reasons.append(f"team member {after[-1]['author']['login']} already commented after {t['version']} ({after[-1]['url']})")

        before = [c for c in team_comments if c["createdAt"] < published]
        thanked = any(THANKED.search(c["body"]) for c in before)
        announced = [c for c in before if ANNOUNCED.search(c["body"]) or any(f"#{p}" in c["body"] or f"/pull/{p}" in c["body"] for p in fixing)]
        linked_pr = any(f"#{p}" in c["body"] or f"/pull/{p}" in c["body"] for c in before for p in fixing)
        if any(NOT_EXACTLY.search(c["body"]) for c in before):
            flags.append("a team comment says it's not exactly/only partly what was asked; check it's clearly resolved")

        others_after = [c for c in item["comments"] if c["createdAt"] >= published and c not in team_comments]
        if others_after:
            flags.append(f"{len(others_after)} non-team comment(s) since the release; read them (the poster may already know, or may have raised something new)")

        later_non_team = [c for c in item["comments"] if not c in team_comments and (c.get("author") or {}).get("login") not in (author, None)]
        if later_non_team and before and before[-1]["createdAt"] < later_non_team[-1]["createdAt"]:
            flags.append(f"last non-team comment is from {later_non_team[-1]['author']['login']}, not the author, after the last team reply")

        if only_body:
            ctx = " | ".join(t["contexts"])[:300]
            if RELATED_WORDS.search(ctx) and not RESOLVE_WORDS.search(ctx):
                flags.append(f"only referenced in a PR body, and the wording looks like 'related': {ctx}")
            else:
                flags.append(f"only referenced in a PR body; confirm the PR resolves it: {ctx}")

        if "[6.x]" in item["title"]:
            flags.append("reported against 6.x; make sure a 6.0 release includes the fix too")

        earlier = [r["version"] for r in older if re.search(rf"craftcms/cms/(issues|pull|discussions)/{n}\b", r["body"])]
        if earlier:
            flags.append(f"also referenced in earlier release(s): {', '.join(earlier[:3])}; the fix may have shipped before")

        own_pr = [p for p in fixing if ((((fetched.get(p) or pr_items.get(p) or {}).get("author")) or {}).get("login")) == author]
        if own_pr:
            flags.append(f"the author wrote the fixing PR (#{own_pr[0]})")

        if item["kind"] == "discussion":
            state = "closed" if item["closed"] else "open"
            group = "open discussions" if not item["closed"] else "closed discussions"
        elif item["kind"] == "issue":
            state = item["state"].lower() + (f" ({item['stateReason'].lower()})" if item.get("stateReason") else "")
            group = "issues"
        else:
            state = "merged" if item.get("merged") else item["state"].lower()
            group = "community PRs"
        is_open = (item["kind"] == "discussion" and not item["closed"]) or (item["kind"] == "issue" and item["state"] == "OPEN")

        reply_target = None
        if item["kind"] == "discussion":
            tops = [c for c in announced if not c.get("isReply")]
            if tops:
                reply_target = {"commentId": tops[-1]["topLevelId"], "url": tops[-1]["url"], "body": tops[-1]["body"][:200]}

        entry = {
            "number": n,
            "kind": item["kind"],
            "group": group,
            "version": t["version"],
            "title": item["title"],
            "url": item["url"],
            "author": author,
            "state": state,
            "needsClosing": is_open,
            "fixingPrs": fixing,
            "via": t["via"],
            "thanked": thanked,
            "announcedBeforeRelease": bool(announced),
            "linkedPr": linked_pr,
            "lastTeamComment": ({"by": before[-1]["author"]["login"], "at": before[-1]["createdAt"], "body": before[-1]["body"][:300], "url": before[-1]["url"]} if before else None),
            "nestedReplyTarget": reply_target,
            "discussionId": item.get("id") if item["kind"] == "discussion" else None,
            "flags": flags + item.get("warnings", []),
        }
        if reasons:
            skipped.append({"number": n, "version": t["version"], "url": item["url"], "title": item["title"], "reason": "; ".join(reasons)})
        else:
            results.append(entry)

    if args.json:
        print(json.dumps({"releases": [{k: r[k] for k in ("version", "publishedAt")} for r in ordered], "items": results, "skipped": skipped}, indent=2))
        return

    print("# Release follow-ups\n")
    for r in ordered:
        print(f"- {r['version']} published {r['publishedAt']}")
    groups = ["open discussions", "closed discussions", "issues", "community PRs"]
    for r in ordered:
        items = [i for i in results if i["version"] == r["version"]]
        print(f"\n## {r['version']} ({len(items)})")
        for g in groups:
            gi = [i for i in items if i["group"] == g]
            if not gi:
                continue
            print(f"\n### {g.capitalize()}\n")
            for i in gi:
                prs = ", ".join(f"#{p}" for p in i["fixingPrs"]) or "—"
                print(f"- **#{i['number']}** [{i['title']}]({i['url']}) by @{i['author']} · {i['state']}{' · **needs closing**' if i['needsClosing'] else ''}")
                print(f"  - fixed by: {prs} · thanked: {'yes' if i['thanked'] else 'no'} · fix announced: {'yes' if i['announcedBeforeRelease'] else 'no'} · linked PR: {'yes' if i['linkedPr'] else 'no'}")
                if i["lastTeamComment"]:
                    body = i["lastTeamComment"]["body"].replace("\n", " ")[:160]
                    print(f"  - last team comment ({i['lastTeamComment']['by']}, {i['lastTeamComment']['at'][:10]}): {body}")
                if i["nestedReplyTarget"]:
                    print(f"  - nested reply target: {i['nestedReplyTarget']['commentId']} ({i['nestedReplyTarget']['url']})")
                for f in i["flags"]:
                    print(f"  - ⚠️ {f}")
    if args.include_skipped:
        print(f"\n## Skipped ({len(skipped)})\n")
        for s in skipped:
            print(f"- #{s['number']} ({s['version']}): {s['reason']}")
    else:
        print(f"\n{len(skipped)} item(s) skipped; rerun with --include-skipped to list them.")


if __name__ == "__main__":
    main()
