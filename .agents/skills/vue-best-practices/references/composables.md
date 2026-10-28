---
title: Composable Organization Patterns
impact: MEDIUM
impactDescription: Well-structured composables improve maintainability, reusability, and update performance
type: best-practice
tags: [vue3, composables, composition-api, code-organization, api-design, readonly, utilities]
---

# Composable Organization Patterns

**Impact: MEDIUM** - Treat composables as reusable, stateful building blocks and keep their code organized by feature concern. This keeps large components maintainable and prevents hard-to-debug mutation and API design issues.

## Task List

- Decompose substantial workflows by responsibility, even when used once; keep simple local state in its component
- Keep related state, actions, and lifecycle cleanup together
- Expose consumer-facing state and actions; keep internal refs and helpers private
- Use options objects for optional configuration, not to hide unrelated dependencies
- Return readonly state when mutations must go through actions
- Keep pure utility functions as plain utilities

## Choose an Extraction Boundary

Extract a composable when a substantial stateful responsibility can be understood and changed through a focused contract, even if it has one caller. Sharing source data does not mean loading, filtering, editing, and DOM interactions must have one implementation. Keep together the state, actions, and cleanup that maintain the responsibility's invariant.

Reactive state alone does not justify extraction. A disclosure toggle can stay in its component. Conversely, a feature-wide name or a narrow return object does not make a large workflow cohesive. Review internal responsibilities as well as exported members.

When an extraction needs most of the caller's refs or mutual callbacks, revise ownership first: move related state and behavior together, pass the capability actually needed, and put cross-capability sequencing in their common coordinator. Keep a unit together when splitting would fragment a shared invariant without hiding meaningful complexity.

**Appropriate local state for a single disclosure:**
```vue
<script setup lang="ts">
import { shallowRef } from 'vue'

const expanded = shallowRef(false)
</script>

<template>
  <button type="button" :aria-expanded="expanded" @click="expanded = !expanded">
    Details
  </button>
  <p v-if="expanded">Additional information</p>
</template>
```

## Extract a Stateful Responsibility, Not the Whole Component Script

A substantial request lifecycle can have one consumer while still benefiting from its own implementation. The UI keeps its input and rendering; the request workflow owns cancellation and loading/error state. This example illustrates a boundary, not a requirement to extract every query or form field.

**BAD:**
```ts
// A different filename does not separate the page's responsibilities.
export function useProductPage() {
  // Loading, request cancellation, drafts, pointer handlers, and dialog focus
  // are all implemented here and returned to the page as one controller.
}
```

**GOOD:**
```ts
// useProductSearch.ts
import { shallowRef, watch, type Ref } from 'vue'

export interface Product { id: string; name: string }
type SearchProducts = (query: string, signal: AbortSignal) => Promise<Product[]>

export function useProductSearch(query: Readonly<Ref<string>>, search: SearchProducts) {
  const products = shallowRef<readonly Product[]>([])
  const pending = shallowRef(false)
  const error = shallowRef<string | null>(null)

  watch(query, async (value, _previous, onCleanup) => {
    const controller = new AbortController()
    let current = true
    onCleanup(() => { current = false; controller.abort() })
    pending.value = true
    error.value = null
    try {
      const result = await search(value, controller.signal)
      if (current) products.value = result
    } catch (cause) {
      if (current) error.value = cause instanceof Error ? cause.message : String(cause)
    } finally {
      if (current) pending.value = false
    }
  }, { immediate: true })

  return { products, pending, error }
}
```

```vue
<!-- ProductSearch.vue -->
<script setup lang="ts">
import { shallowRef } from 'vue'
import { searchProducts } from './product-api'
import { useProductSearch } from './useProductSearch'

const query = shallowRef('')
const { products, pending, error } = useProductSearch(query, searchProducts)
const emit = defineEmits<{ select: [id: string] }>()
</script>

<template>
  <label>Search products <input v-model="query" /></label>
  <p v-if="error" role="alert">{{ error }}</p>
  <p v-if="pending" role="status">Loading…</p>
  <ul>
    <li v-for="product in products" :key="product.id">
      <button type="button" @click="emit('select', product.id)">{{ product.name }}</button>
    </li>
  </ul>
</template>
```

A larger feature can compose additional focused capabilities through their relevant inputs/actions. Keep sequencing that spans them in the caller; do not move their private refs back into a universal controller. A child component may call its own composables, so ancestors need not receive and forward all their state.

## Keep Composable APIs Focused

- Return the state and operations consumers need. Keep request handles, bookkeeping, and private helpers internal.
- When composing composables, preserve meaningful capability boundaries rather than re-exporting every internal member. Grouping the same internals under nested objects changes naming, not ownership.
- Create shared state once per intended scope and pass or provide that instance. Calling a stateful composable in each consumer usually creates separate state.
- Keep DOM refs, focus, and pointer handling with their owning UI or composable. Share only the state or operations needed for coordination.

## Use Options Object Pattern for Composable Parameters

**BAD:**
```javascript
export function useFetch(url, method, headers, timeout, retries, immediate) {
  // hard to read and easy to misorder
}

useFetch('/api/users', 'GET', null, 5000, 3, true)
```

**GOOD:**
```javascript
export function useFetch(url, options = {}) {
  const {
    method = 'GET',
    headers = {},
    timeout = 30000,
    retries = 0,
    immediate = true
  } = options

  // implementation
  return { method, headers, timeout, retries, immediate }
}

useFetch('/api/users', {
  method: 'POST',
  timeout: 5000,
  retries: 3
})
```

```typescript
interface UseCounterOptions {
  initial?: number
  min?: number
  max?: number
  step?: number
}

export function useCounter(options: UseCounterOptions = {}) {
  const { initial = 0, min = -Infinity, max = Infinity, step = 1 } = options
  // implementation
}
```

## Return Readonly State with Explicit Actions

**BAD:**
```javascript
export function useCart() {
  const items = ref([])
  const total = computed(() => items.value.reduce((sum, item) => sum + item.price, 0))
  return { items, total } // any consumer can mutate directly
}

const { items } = useCart()
items.value.push({ id: 1, price: 10 })
```

**GOOD:**
```javascript
import { ref, computed, readonly } from 'vue'

export function useCart() {
  const _items = ref([])

  const total = computed(() =>
    _items.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
  )

  function addItem(product, quantity = 1) {
    const existing = _items.value.find(item => item.id === product.id)
    if (existing) {
      existing.quantity += quantity
      return
    }
    _items.value.push({ ...product, quantity })
  }

  function removeItem(productId) {
    _items.value = _items.value.filter(item => item.id !== productId)
  }

  return {
    items: readonly(_items),
    total,
    addItem,
    removeItem
  }
}
```

## Keep Utilities as Utilities

**BAD:**
```javascript
export function useFormatters() {
  const formatDate = (date) => new Intl.DateTimeFormat('en-US').format(date)
  const formatCurrency = (amount) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount)
  return { formatDate, formatCurrency }
}

const { formatDate } = useFormatters()
```

**GOOD:**
```javascript
// utils/formatters.js
export function formatDate(date) {
  return new Intl.DateTimeFormat('en-US').format(date)
}

export function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}
```

```javascript
// composables/useInvoiceSummary.js
import { computed } from 'vue'
import { formatCurrency } from '@/utils/formatters'

export function useInvoiceSummary(invoiceRef) {
  const totalLabel = computed(() => formatCurrency(invoiceRef.value.total))
  return { totalLabel }
}
```
