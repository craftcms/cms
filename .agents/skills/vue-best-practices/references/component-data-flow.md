---
title: Component Data Flow Best Practices
impact: HIGH
impactDescription: Clear data flow between components prevents state bugs, stale UI, and brittle coupling
type: best-practice
tags: [vue3, props, emits, v-model, provide-inject, data-flow, typescript]
---

# Component Data Flow Best Practices

**Impact: HIGH** - Vue components stay reliable when data flow is explicit: props go down, events go up, `v-model` handles two-way bindings, and provide/inject supports cross-tree dependencies. Blurring these boundaries leads to stale state, hidden coupling, and hard-to-debug UI.

The main principle of data flow in Vue.js is **Props Down / Events Up**. This is the most maintainable default, and one-way flow scales well.

## Task List

- Treat props as read-only inputs
- Use reactive props destructure in `<script setup>` in Vue 3.5+
- Use props/emit for component communication; reserve refs for imperative actions
- When refs are required for imperative APIs, type them with template refs
- Emit events instead of mutating parent state directly
- Use `defineModel` for v-model in Vue 3.4+
- Handle v-model modifiers deliberately in child components
- Use typed, scoped provide/inject for shared descendant dependencies when intermediate components only relay them
- Keep mutations in the provider or expose explicit actions
- In TypeScript projects, prefer type-based `defineProps`, `defineEmits`, and `InjectionKey`

## Props: One-Way Data Down

Props are inputs. Do not mutate them in the child.

**BAD:**
```vue
<script setup>
const props = defineProps({ count: Number })

function increment() {
  props.count++
}
</script>
```

**GOOD:**

If state needs to change, emit an event, use `v-model` or create a local copy.

## Use reactive props destructure

For Vue 3.5+: destructure directly from `defineProps()` in `<script setup>` and use native default values. Accesses in the same block stay reactive. Before Vue 3.5, keep `props.title` access and use `withDefaults` for type-based defaults.

Pass destructured props to `watch` or composables through getters to preserve reactivity.

**BAD:**
```vue
<script setup lang="ts">
import { watch } from 'vue'

const { title = 'Untitled', count } = defineProps<{
  title?: string
  count: number
}>()

watch(count, (value) => console.log(value))
</script>
```

**GOOD:**
```vue
<script setup lang="ts">
import { computed, watch } from 'vue'

const { title = 'Untitled', count } = defineProps<{
  title?: string
  count: number
}>()

const heading = computed(() => `${title.trim()} (${count})`)
watch(() => count, (value) => console.log(value))
</script>
```

## Prefer props/emit over component refs

**BAD:**
```vue
<script setup>
import { ref } from 'vue'
import UserForm from './UserForm.vue'

const formRef = ref(null)

function submitForm() {
  if (formRef.value.isValid) {
    formRef.value.submit()
  }
}
</script>

<template>
  <UserForm ref="formRef" />
  <button @click="submitForm">Submit</button>
</template>
```

**GOOD:**
```vue
<script setup>
import UserForm from './UserForm.vue'

function handleSubmit(formData) {
  api.submit(formData)
}
</script>

<template>
  <UserForm @submit="handleSubmit" />
</template>
```

## Type component refs when imperative access is required

Prefer props/emits by default. When a parent must call an exposed child method, type the ref explicitly and expose only the intended API from the child with `defineExpose`.

**BAD:**
```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DialogPanel from './DialogPanel.vue'

const panelRef = ref(null)

onMounted(() => {
  panelRef.value.open()
})
</script>

<template>
  <DialogPanel ref="panelRef" />
</template>
```

**GOOD:**
```vue
<!-- DialogPanel.vue -->
<script setup lang="ts">
function open() {}

defineExpose({ open })
</script>
```

```vue
<!-- Parent.vue -->
<script setup lang="ts">
import { onMounted, useTemplateRef } from 'vue'
import DialogPanel from './DialogPanel.vue'

// Vue 3.5+ with useTemplateRef
const panelRef = useTemplateRef('panelRef')

// Before Vue 3.5 with manual typing and ref
// const panelRef = ref<InstanceType<typeof DialogPanel> | null>(null)

onMounted(() => {
  panelRef.value?.open()
})
</script>

<template>
  <DialogPanel ref="panelRef" />
</template>
```

## Emits: Explicit Events Up

Component events do not bubble. Re-emit events that belong to the wrapper's public contract. If most of its API only relays child state and events, reassess the boundary or use scoped context for genuinely shared dependencies.

**BAD:**
```vue
<!-- Parent expects "saved" from grandchild, but it won't bubble -->
<Child @saved="onSaved" />
```

**GOOD:**
```vue
<!-- Child.vue -->
<script setup>
const emit = defineEmits(['saved'])

function onGrandchildSaved(payload) {
  emit('saved', payload)
}
</script>

<template>
  <Grandchild @saved="onGrandchildSaved" />
</template>
```

**Event naming:** use kebab-case in templates and camelCase in script:
```vue
<script setup>
const emit = defineEmits(['updateUser'])
</script>

<template>
  <ProfileForm @update-user="emit('updateUser', $event)" />
</template>
```

## `v-model`: Predictable Two-Way Bindings

Use `defineModel` by default for component bindings and emit updates on input. Only use the `modelValue` + `update:modelValue` pattern if you are on Vue < 3.4.

**BAD:**
```vue
<script setup>
const props = defineProps({ value: String })
</script>

<template>
  <input :value="props.value" @input="$emit('input', $event.target.value)" />
</template>
```

**GOOD (Vue 3.4+):**
```vue
<script setup>
const model = defineModel({ type: String })
</script>

<template>
  <input v-model="model" />
</template>
```

**GOOD (Vue < 3.4):**
```vue
<script setup>
const props = defineProps({ modelValue: String })
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <input
    :value="props.modelValue"
    @input="emit('update:modelValue', $event.target.value)"
  />
</template>
```

If you need the updated value immediately after a change, use the input event value or `nextTick` in the parent.

## Diagnose Forwarding Before Choosing a Transport

When intermediate components mainly relay a descendant's private interactions, inspect ownership before changing props to injection or merging components:

- Move UI-local state, DOM refs, listeners, and their cleanup to the component that owns that interaction, or a composable used there.
- Keep data needed by multiple consumers at an owner whose lifetime matches their shared use. Coordinate cross-feature actions through that owner's public contract.
- Use props/emits for direct contracts and scoped context for shared descendant dependencies. A useful UI component can retain its own boundary even when its descendants need common interaction state.
- Merge a wrapper when it adds no useful UI, policy, or encapsulation. Do not merge substantial distinct responsibilities solely to avoid forwarding.

**BAD:**
```vue
<!-- Ancestors know the private pointer interaction of an item. -->
<ItemList :dragging-id="draggingId" @pointer-move="moveItem" @pointer-up="finishDrag" />
```

**GOOD:**
```vue
<!-- The owning interaction translates DOM events into a feature operation. -->
<ItemList :items="items" @reorder="reorderItems" />
```

The list owns pointer tracking and cleanup; its consumer owns the ordering policy. If internal list descendants share drag state, provide only that scoped capability. Ordinary row display data can still use props. A controlled low-level component may intentionally expose DOM events; the issue is incidental relaying across unrelated feature boundaries.

## Provide/Inject: Scoped Shared Dependencies

Prefer props/emits for direct, explicit contracts. Use provide/inject when related descendants share state or operations and intermediate components only relay them. Check that those component boundaries are useful before introducing context.

- Place the provider at the nearest common ancestor that owns the dependency; match its lifetime to the intended scope.
- Create provider-owned state per instance. Consumer helpers inject that instance; they do not create a fallback copy.
- Use `InjectionKey` with consumer-facing readonly state and explicit mutation actions. Avoid providing an entire application controller.
- Call provider/consumer helpers during setup. Report a missing required provider clearly; defaults are appropriate only for intentionally optional dependencies.

**BAD:**
```ts
// Provider
provide('selection', reactive({ selectedId: null }))

// Consumer
const selection = inject('selection')
selection.selectedId = 'item-1'
```

**GOOD:**
```ts
// selection-context.ts
import { inject, provide, readonly, shallowRef } from 'vue'
import type { InjectionKey, Ref } from 'vue'

interface SelectionContext {
  selectedId: Readonly<Ref<string | null>>
  select: (id: string | null) => void
}

const selectionKey: InjectionKey<SelectionContext> = Symbol('selection')

export function provideSelection() {
  const selectedId = shallowRef<string | null>(null)
  const context: SelectionContext = {
    selectedId: readonly(selectedId),
    select: (id) => { selectedId.value = id },
  }
  provide(selectionKey, context)
  return context
}

export function useSelectionContext(): SelectionContext {
  const context = inject(selectionKey)
  if (!context) throw new Error('useSelectionContext requires a selection provider')
  return context
}
```

Call `provideSelection()` in the owning ancestor's setup. Descendants use the shared contract:

```vue
<script setup lang="ts">
import { useSelectionContext } from './selection-context'

defineProps<{ id: string }>()
const { selectedId, select } = useSelectionContext()
</script>

<template>
  <button type="button" :aria-pressed="selectedId === id" @click="select(id)">
    Select
  </button>
</template>
```

## Use TypeScript Contracts for Public Component APIs

Type component boundaries with `defineProps` and `defineEmits` so mismatched inputs and event payloads fail at compile time.

**BAD:**
```vue
<script setup lang="ts">
const props = defineProps({ userId: String })
const emit = defineEmits(['save'])

emit('save', 123)
</script>
```

**GOOD:**
```vue
<script setup lang="ts">
const props = defineProps<{ userId: string }>()
const emit = defineEmits<{
  save: [payload: { id: string; draft: boolean }]
}>()

function saveDraft() {
  emit('save', { id: props.userId, draft: true })
}
</script>

<template>
  <button type="button" @click="saveDraft">Save draft</button>
</template>
```
