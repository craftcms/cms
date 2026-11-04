---
name: vue-best-practices
description: MUST be used for Vue.js tasks. Strongly recommends Composition API with `<script setup>` and TypeScript as the standard approach. Covers Vue 3, SSR, Volar, vue-tsc. Load for any Vue, .vue files, Vue Router, Pinia, or Vite with Vue work. ALWAYS use Composition API unless the project explicitly requires Options API.
license: MIT
metadata:
  author: github.com/vuejs-ai
  version: "19.1.0"
---

# Vue Best Practices Workflow

Use this skill as an instruction set. Follow the workflow in order unless the user explicitly asks for a different order.

## Core Principles
- **Keep state predictable:** one source of truth, derive everything else.
- **Make data flow explicit:** Props down, Events up for most cases.
- **Decompose by responsibility:** keep each implementation focused and the contracts between implementations explicit.
- **Avoid unnecessary re-renders:** use computed properties and watchers wisely.
- **Readability counts:** write clear, self-documenting code.

## 1) Confirm architecture before coding (required)

- Default stack: Vue 3 + Composition API + `<script setup lang="ts">`.
- If the project explicitly uses Options API, load `vue-options-api-best-practices` skill if available.
- If the project explicitly uses JSX, load `vue-jsx-best-practices` skill if available.

### 1.1 Must-read core references (required)

- Before implementing any Vue task, make sure to read and apply these core references:
  - `references/reactivity.md`
  - `references/sfc.md`
  - `references/component-data-flow.md`
  - `references/composables.md`
- Keep these references in active working context for the entire task, not only when a specific issue appears.

### 1.2 Plan component boundaries before coding (required)

For a non-trivial feature or structural refactor, sketch responsibilities, state owners, and the contracts that cross their boundaries. For local fixes, preserve existing boundaries unless they obstruct the change.

- Identify substantial UI responsibilities, stateful workflows, and DOM lifecycles in the affected feature. Sketch their owners, consumers, and the contracts needed for coordination.
- Judge cohesion by behavior that changes together or maintains a shared invariant. Being on the same page, reading the same data, or having only one consumer does not by itself justify one implementation.
- Let root/route components compose features and coordinate page behavior. Apply the same responsibility review inside feature components and composables; moving a whole feature to a child or a `useFeature()` function is not sufficient decomposition.
- Follow the project's layout and scale the design to the affected behavior. A local fix does not require a project-wide component map or new architectural layers.

## 2) Apply essential Vue foundations (required)

These are essential, must-know foundations. Apply all of them in every Vue task using the core references already loaded in section `1.1`.

### Reactivity

- Must-read reference from `1.1`: [reactivity](references/reactivity.md)
- Keep source state minimal (`ref`/`reactive`), derive everything possible with `computed`.
- Use watchers for side effects if needed.
- Avoid recomputing expensive logic in templates.

### SFC structure and template safety

- Must-read reference from `1.1`: [sfc](references/sfc.md)
- Keep SFC sections in this order: `<script>` → `<template>` → `<style>`.
- Keep SFC responsibilities cohesive; use the boundary criteria below when splitting.
- Keep templates declarative; move branching/derivation to script.
- Apply Vue template safety rules (`v-html`, list rendering, conditional rendering choices).

### Choose useful boundaries

- Extract substantial, separable UI responsibilities and stateful workflows when doing so reduces the code a consumer or maintainer must understand. Single-use components and composables are valid; reuse is not a prerequisite. Keep the state and event handling needed by a UI responsibility with that UI.
- During a structural refactor, implement useful boundaries for the responsibilities identified above. Review what remains together as well as what was extracted. A small public API can hide an implementation that still needs decomposition.
- Treat file length, UI section count, and API size as review signals, not extraction thresholds. Keep a small cohesive production feature in one SFC when extra boundaries would not hide meaningful complexity.
- If a split adds extensive forwarding or mutual callbacks, first move local state and DOM behavior to their UI owner, then identify genuinely shared dependencies and their lifetime. Use explicit contracts or scoped context as appropriate. Merge only when the boundary has no useful responsibility to encapsulate, not merely to reduce wiring.
- Group inputs only when they form a cohesive concept. Passing a whole controller, grouping unrelated handlers, or adding catch-all events does not reduce the dependencies consumers must understand.

### Component data flow

- Must-read reference from `1.1`: [component-data-flow](references/component-data-flow.md)
- Use props down, events up as the primary model.
- Use `v-model` only for true two-way component contracts.
- Use typed, scoped provide/inject for shared descendant dependencies when intermediate components only relay them; reassess component boundaries first. See the data-flow reference for scope and ownership.
- Keep contracts explicit and typed with `defineProps`, `defineEmits`, and `InjectionKey` as needed.

### Composables

- Must-read reference from `1.1`: [composables](references/composables.md)
- Compose substantial stateful behavior from focused capabilities, including single-use workflows that improve code organization. Keep simple component-local logic local; use plain functions for pure calculations.
- Distinguish implementation ownership from the public facade. A coordinator connects capabilities and implements cross-capability operations; it should not contain all their implementations or re-export all their internals.
- Return consumer-facing state and actions. If a consumer needs unrelated groups of internals, review that consumer's responsibilities too. Encapsulate reactive bookkeeping so consumers can read current values without manually triggering invalidation.
- Keep DOM interaction and cleanup with its owning UI or composable. Preserve the explicit coordination other features need, such as committing a pending draft before saving.

## 3) Consider optional features only when requirements call for them

### 3.1 Standard optional features

Do not add these by default. Load the matching reference only when the requirement exists.

- Slots: parent needs to control child content/layout -> [component-slots](references/component-slots.md)
- Fallthrough attributes: wrapper/base components must forward attrs/events safely -> [component-fallthrough-attrs](references/component-fallthrough-attrs.md)
- Built-in component `<KeepAlive>` for stateful view caching -> [component-keep-alive](references/component-keep-alive.md)
- Built-in component `<Teleport>` for overlays/portals -> [component-teleport](references/component-teleport.md)
- Built-in component `<Suspense>` for async subtree fallback boundaries -> [component-suspense](references/component-suspense.md)
- Animation-related features: pick the simplest approach that matches the required motion behavior.
  - Built-in component `<Transition>` for enter/leave effects -> [transition](references/component-transition.md)
  - Built-in component `<TransitionGroup>` for animated list mutations -> [transition-group](references/component-transition-group.md)
  - Class-based animation for non-enter/leave effects -> [animation-class-based-technique](references/animation-class-based-technique.md)
  - State-driven animation for user-input-driven animation -> [animation-state-driven-technique](references/animation-state-driven-technique.md)

### 3.2 Less-common optional features

Use these only when there is explicit product or technical need.

- Directives: behavior is DOM-specific and not a good composable/component fit -> [directives](references/directives.md)
- Async components: heavy/rarely-used UI should be lazy loaded -> [component-async](references/component-async.md)
- Render functions only when templates cannot express the requirement -> [render-functions](references/render-functions.md)
- Plugins when behavior must be installed app-wide -> [plugins](references/plugins.md)
- State management patterns: app-wide shared state crosses feature boundaries -> [state-management](references/state-management.md)

## 4) Run performance optimization after behavior is correct

Performance work is a post-functionality pass. Do not optimize before core behavior is implemented and verified.

- Large list rendering bottlenecks -> [perf-virtualize-large-lists](references/perf-virtualize-large-lists.md)
- Static subtrees re-rendering unnecessarily -> [perf-v-once-v-memo-directives](references/perf-v-once-v-memo-directives.md)
- Over-abstraction in hot list paths -> [perf-avoid-component-abstraction-in-lists](references/perf-avoid-component-abstraction-in-lists.md)
- Expensive updates triggered too often -> [updated-hook-performance](references/updated-hook-performance.md)

## 5) Final self-check before finishing

- Core behavior works and matches requirements.
- All must-read references were read and applied.
- Reactivity model is minimal and predictable.
- SFC structure and template rules are followed.
- Both sides of the boundary review pass: substantial separable responsibilities are not still concentrated in one implementation, and extracted units hide useful complexity rather than merely forward it.
- Public contracts are typed and hide internals. Trace a representative local change through the code: it should stay with its responsible UI/workflow and legitimate coordination, without unrelated forwarding edits.
- State has explicit ownership and lifetime; shared consumers use the same intended instance, independent instances remain independent, and side effects clean up with their owner.
- Refactors preserve existing behavior, including pending work and cross-feature operations. Use the project's applicable tests; API or file-size reductions alone are not evidence of success.
- Optional features are used only when requirements demand them.
- Performance changes were applied only after functionality was complete.
