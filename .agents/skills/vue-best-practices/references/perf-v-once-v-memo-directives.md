---
title: Use v-once and v-memo to Skip Unnecessary Updates
impact: MEDIUM
impactDescription: v-once skips all future updates for runtime-bound content that never changes; v-memo conditionally memoizes subtrees
type: efficiency
tags: [vue3, performance, v-once, v-memo, optimization, directives]
---

# Use v-once and v-memo to Skip Unnecessary Updates

**Impact: MEDIUM** - When a component re-renders, its render function re-creates VNodes for the dynamic parts of its template. For content that never changes or changes infrequently, `v-once` and `v-memo` let Vue reuse the previous VNodes, skipping VNode creation and diffing for that subtree.

Use `v-once` for content that uses runtime data but never needs updating, and `v-memo` only when profiling identifies costly list rendering. Incomplete memo dependencies can render stale data.

## Task List

- Apply `v-once` to elements that use runtime data but never need updating
- Apply `v-memo` only when profiling identifies costly list rendering
- Include dependencies whose values change whenever the memoized VNode output must change. Object entries are compared by identity: include `item` for replacements, and `item.name` when in-place edits must update text or props rendered by this subtree
- Verify correct updates and use Vue DevTools to confirm a measurable gain before retaining the optimization

## v-once: Render Once, Never Update

**BAD:**
```vue
<template>
  <!-- BAD: VNodes are re-created on every re-render of this component -->
  <div class="terms-content">
    <h1>Terms of Service</h1>
    <p>Version: {{ termsVersion }}</p>
    <div v-html="termsContent"></div>
  </div>

  <!-- This content NEVER changes, but its VNodes are re-created every render -->
  <footer>
    <p>Copyright {{ copyrightYear }} {{ companyName }}</p>
  </footer>
</template>
```

**GOOD:**
```vue
<script setup>
// These values are set once at component creation
const termsVersion = '2.1'
const termsContent = fetchedTermsHTML
const copyrightYear = 2024
const companyName = 'Acme Corp'
</script>

<template>
  <!-- GOOD: Rendered once, skipped on all future updates -->
  <div class="terms-content" v-once>
    <h1>Terms of Service</h1>
    <p>Version: {{ termsVersion }}</p>
    <div v-html="termsContent"></div>
  </div>

  <!-- v-once tells Vue this never needs to update -->
  <footer v-once>
    <p>Copyright {{ copyrightYear }} {{ companyName }}</p>
  </footer>
</template>
```

## v-memo: Conditional Memoization for Lists

When list rendering is a measured bottleneck, memoization can skip row VNode creation and diffing. Without it, selection changes re-create every row's VNodes even though `ExpensiveComponent` skips parent-triggered updates with unchanged props.

**BAD:**
```vue
<template>
  <div v-for="item in list" :key="item.id">
    <div :class="{ selected: item.id === selectedId }">
      <ExpensiveComponent :data="item" />
    </div>
  </div>
</template>
```

With the dependencies below, selection changes re-create VNodes only for rows whose selection state changes; other rows reuse cached VNodes.

Preserve references for unchanged items to improve cache reuse. Recreating every item when a computed list recomputes invalidates every row's memo.

In-place mutations do not invalidate the `item` dependency, but `ExpensiveComponent` still updates if its render reads the mutated reactive field. For this example, selection-only dependencies are sufficient when items are never replaced.

**GOOD:**
```vue
<script setup>
import { ref } from 'vue'

const list = ref([/* many items */])
const selectedId = ref(null)
</script>

<template>
  <!-- Include item so replacements refresh the child prop even when selection is unchanged. -->
  <div
    v-for="item in list"
    :key="item.id"
    v-memo="[item, item.id === selectedId]"
  >
    <div :class="{ selected: item.id === selectedId }">
      <ExpensiveComponent :data="item" />
    </div>
  </div>
</template>
```

## v-memo with Multiple Dependencies

```vue
<script setup>
const selectedId = ref(null)
const editingId = ref(null)
const items = ref([/* ... */])
</script>

<template>
  <!-- Rows update only when the item, its selection, or its editing state changes -->
  <div
    v-for="item in items"
    :key="item.id"
    v-memo="[item, item.id === selectedId, item.id === editingId]"
  >
    <ItemCard
      :item="item"
      :selected="item.id === selectedId"
      :editing="item.id === editingId"
    />
  </div>
</template>
```

## v-memo with Empty Array = v-once

```vue
<template>
  <!-- v-memo="[]" is equivalent to v-once -->
  <div v-for="item in staticList" :key="item.id" v-memo="[]">
    {{ item.name }}
  </div>
</template>
```

## When NOT to Use These Directives

```vue
<template>
  <!-- DON'T: Content that DOES need to update -->
  <div v-once>
    <span>Count: {{ count }}</span>  <!-- count won't update! -->
  </div>

  <!-- DON'T: Omit values the subtree passes to child components -->
  <div v-memo="[selected]">
    <InputField v-model="item.name" />  <!-- modelValue stays stale until selected changes -->
  </div>

  <!-- DON'T: When the memoization benefit is minimal -->
  <span v-once>{{ simpleText }}</span>  <!-- Negligible gain -->
</template>
```

## Performance Comparison

| Scenario | Without Directive | With v-once/v-memo |
|----------|-------------------|-------------------|
| Runtime-bound content that never changes, component re-renders 100x | VNodes re-created 100x | VNodes created once |
| 1000 items, selection changes | 1000 rows' VNodes re-created and diffed | At most 2 rows re-created; remaining rows reused, assuming unchanged items |
| Parent re-render with stable child props and slots | Not re-rendered | Not re-rendered |

## Debugging Memoized Components

```vue
<script setup>
import { onUpdated } from 'vue'

// Add this to the child inside the memoized subtree (e.g. ItemCard), not to the
// component that owns v-memo: the owner's onUpdated fires on every owner re-render,
// even when every memo hits. If the child shows stale data and this doesn't fire,
// a dependency may be missing from the v-memo dependency array.
onUpdated(() => {
  console.log('Component updated')
})
</script>
```
