# Scaffold notes — `@craftcms/garnish`

This package is the modern, tree-shakeable TypeScript rewrite of Craft CMS's
Garnish UI library: TypeScript, ESM-only, no jQuery. This document covers the
tooling/config scaffold (no library source yet — the implementation team adds
that next).

## Toolchain

The toolchain mirrors the modern `@craftcms/cp` package so the monorepo stays
consistent.

| Concern        | Choice                                  | Why                                                                                  |
| -------------- | --------------------------------------- | ------------------------------------------------------------------------------------ |
| Bundler        | **tsdown** (Rolldown-based)             | Same bundler `@craftcms/cp` uses; native ESM, multi-entry code-splitting, built-in `.d.ts` emit. |
| Output         | ESM only, `.d.ts` declarations, sourcemaps | Tree-shakeable library output; matches cp's `format: ['esm']` + `dts: true`.       |
| Type config    | `@total-typescript/tsconfig/bundler/dom` | Same base preset the repo root tsconfig uses. Strict, ES2022, `moduleResolution: bundler`. |
| Test runner    | **Vitest** + `happy-dom`                | Same as cp. `happy-dom` gives a DOM without a browser for fast unit tests.           |
| Coverage       | `@vitest/coverage-v8`                   | Matches cp.                                                                           |
| Format         | **Prettier**                            | Repo-wide convention (root `prettier`).                                              |
| Workspace      | npm workspaces (`packages/*`)           | Auto-included by the root `package.json` `workspaces` glob — no manual registration needed. |

No jQuery anywhere. The package is zero-dependency by design (only
devDependencies). Peer deps, when they appear, go in `tsdown.config.ts` under
`deps.neverBundle` and in `package.json` `peerDependencies`.

## Entry points

Two build entries, mapped in `package.json` `exports`:

- `.` → `./dist/index.js` (+ `index.d.ts`) — modern core + components.
- `./compat` → `./dist/compat.js` (+ `compat.d.ts`) — reserved opt-in legacy
  compatibility layer. `src/compat.ts` is a placeholder (`export {}`) so the
  build resolves; the implementation team populates it later.

tsdown/Rolldown code-splits shared chunks between these two entries
automatically.

## Files

```
packages/craftcms-garnish/
├── package.json          # @craftcms/garnish, type: module, exports map, scripts
├── tsconfig.json         # extends @total-typescript bundler/dom preset, strict
├── tsdown.config.ts      # ESM library build, dual entry (index + compat), dts
├── vitest.config.ts      # globals + happy-dom
├── .gitignore            # /dist /node_modules /coverage
├── .nvmrc                # v22
├── docs/                 # (pre-existing — untouched)
└── src/
    ├── index.ts          # exports VERSION const (placeholder)
    ├── compat.ts         # export {} placeholder (TODO: legacy compat layer)
    └── index.test.ts     # smoke test
```

## Commands

Run from the repo root with the workspace flag, or from inside the package dir.

```bash
# Install (run once at repo root — picks up the new workspace)
npm install

# Build ESM + declarations into dist/
npm run build -w @craftcms/garnish

# Watch-mode build
npm run dev -w @craftcms/garnish

# Type-check only (no emit)
npm run check:types -w @craftcms/garnish      # alias: typecheck

# Run tests once / watch / with coverage
npm run test -w @craftcms/garnish
npm run test:dev -w @craftcms/garnish
npm run test:coverage -w @craftcms/garnish

# Formatting
npm run format -w @craftcms/garnish           # write
npm run check:format -w @craftcms/garnish     # check

# Combined gate (typecheck + format check)
npm run lint -w @craftcms/garnish
```

## Verification (scaffold)

The skeleton was proven green:

- `npm install` (repo root) — workspace picked up, deps installed.
- `npm run check:types -w @craftcms/garnish` — passes.
- `npm run build -w @craftcms/garnish` — emits `dist/index.js`,
  `dist/index.d.ts`, `dist/compat.js`, `dist/compat.d.ts`, `dist/index.js.map`.
- `npm run test -w @craftcms/garnish` — 2 tests pass.
- `npm run lint -w @craftcms/garnish` — typecheck + Prettier both clean.
