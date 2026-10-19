<script setup lang="ts">
  /**
   * “Ask AI”, as a slideout panel: a prompt step, then a review step
   * whose accepted values are written into the editor that opened it.
   *
   * Opened with `openSlideoutWith()` rather than `openSlideout()`: the review
   * holds the generated values client-side until they're applied, so there's no
   * screen to fetch.
   */
  import {actionClient, t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    shallowRef,
    useTemplateRef,
  } from 'vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSlideout} from '@/common/slideouts';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import AskAiDiff from './AskAiDiff.vue';
  import AskAiFindings from './AskAiFindings.vue';
  import {renderDiff} from '@/common/utils/diff';
  import type {UiPayload} from '@/modules/ui/types';
  import AskAiController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AskAiController';
  import {
    takeAskAiContext,
    type AskAiFinding,
    type AskAiProposal,
    type AskAiResult,
  } from './ask-ai';

  interface FormResponse {
    ui: UiPayload;
    title: string;
    submitLabel: string;
  }

  const props = defineProps<{
    contextId: string;
  }>();
  const context = takeAskAiContext(props.contextId);

  const slideout = useSlideout();
  const promptForm = shallowRef<FormResponse | null>(null);
  const step = shallowRef<'prompt' | 'review'>('prompt');

  /**
   * How long Apply ignores presses after the review step appears. Apply takes
   * Generate's place, so a repeated tap, such as one made while a phone's
   * keyboard delays the first, would apply the values without a review. The
   * button stays enabled, so it isn't announced as unavailable.
   */
  const APPLY_DELAY = 700;
  const applyLocked = shallowRef(false);
  const error = shallowRef<string | null>(null);
  const errors = shallowRef<UiPayload['errors']>([]);
  const proposals = shallowRef<AskAiProposal[]>([]);
  const summary = shallowRef<string | null>(null);
  const findings = shallowRef<AskAiFinding[]>([]);
  const selected = shallowRef(new Set<string>());
  const renderer = useTemplateRef<InstanceType<typeof UiRenderer>>('renderer');
  const reviewHeading = useTemplateRef<HTMLElement>('reviewHeading');
  const promptBody = useTemplateRef<HTMLElement>('promptBody');

  /**
   * Drives the panel's primary button and its unsaved-changes prompt: a typed
   * prompt, or generated values that haven't been applied, would be lost.
   */
  const form = useForm({prompt: '', reviewing: false});

  /** A review can come back with feedback and nothing to apply. */
  const hasProposals = computed(() => proposals.value.length > 0);

  /**
   * Findings for fields without a proposal, grouped by field, and for the
   * content as a whole.
   */
  const otherFindingGroups = computed(() => {
    const groups = new Map<
      string,
      {key: string; label: string; findings: AskAiFinding[]}
    >();

    for (const finding of findings.value) {
      if (
        proposals.value.some(
          (proposal) => proposal.layoutElementUid === finding.layoutElementUid
        )
      ) {
        continue;
      }

      const key = finding.layoutElementUid ?? 'overall';
      const group = groups.get(key) ?? {
        key,
        label: finding.label ?? t('Overall'),
        findings: [],
      };
      group.findings.push(finding);
      groups.set(key, group);
    }

    return [...groups.values()];
  });

  /** Each proposal with its findings, and what its card header shows. */
  const cards = computed(() =>
    proposals.value.map((proposal) => {
      const cardFindings = findings.value.filter(
        (finding) => finding.layoutElementUid === proposal.layoutElementUid
      );
      const quotes = cardFindings.flatMap((finding) =>
        finding.quote ? [finding.quote] : []
      );
      const diff = proposal.diff
        ? renderDiff(proposal.diff.current, proposal.diff.proposed, quotes)
        : null;

      return {
        proposal,
        findings: cardFindings,
        quotes,
        /** Whether the diff hides unchanged text, so the full field can be shown. */
        collapsible: diff?.collapsed ?? false,
        badge: !proposal.diff
          ? null
          : proposal.diff.current
            ? t('{num, plural, =1{# edit} other{# edits}}', {num: diff!.edits})
            : t('New'),
      };
    })
  );

  const expandedFields = shallowRef(new Set<string>());

  function toggleExpanded(uid: string): void {
    const next = new Set(expandedFields.value);

    if (!next.delete(uid)) {
      next.add(uid);
    }

    expandedFields.value = next;
  }

  function notesLabel(num: number): string {
    return t('{num, plural, =1{# note} other{# notes}}', {num});
  }

  /** A field-level request writes one field, so there's nothing to choose. */
  const isFieldLevel = computed(() => Boolean(context.params.layoutElementUid));

  useAppLayout(() => ({
    title: promptForm.value?.title ?? t('Ask AI'),
    form,
    defaultFormActions: [],
    submitButtonLabel:
      step.value === 'prompt'
        ? (promptForm.value?.submitLabel ?? t('Ask'))
        : hasProposals.value
          ? t('Apply')
          : t('Done'),
    onSave: () =>
      void (step.value === 'prompt'
        ? generate()
        : hasProposals.value
          ? apply()
          : slideout?.close({force: true})),
  }));

  let request: AbortController | null = null;

  onMounted(async () => {
    try {
      const {data} = await actionClient.post<FormResponse>(
        AskAiController.form.url(),
        {...context.values(), ...context.params}
      );
      promptForm.value = data;
    } catch (exception: any) {
      Craft.cp?.displayError?.(
        exception?.response?.data?.message || t('A server error occurred.')
      );
      slideout?.close({force: true});
    }
  });

  onBeforeUnmount(() => request?.abort());

  function onPromptChange(): void {
    const prompt = renderer.value?.currentValues().prompt;
    form.prompt = typeof prompt === 'string' ? prompt : '';
  }

  async function generate(): Promise<void> {
    if (!renderer.value || form.processing) {
      return;
    }

    form.processing = true;
    error.value = null;
    errors.value = [];
    request = new AbortController();

    try {
      const {data} = await actionClient.post<AskAiResult>(
        AskAiController.ask.url(),
        {
          ...context.values(),
          ...renderer.value.currentValues(),
          ...context.params,
          namespace: context.namespace,
        },
        {signal: request.signal}
      );

      proposals.value = data.proposals;
      summary.value = data.summary;
      findings.value = data.findings;
      selected.value = new Set(
        data.proposals.map((proposal) => proposal.layoutElementUid)
      );
      step.value = 'review';

      if (data.proposals.length) {
        form.reviewing = true;
      } else {
        // Feedback alone leaves nothing to lose when the panel closes.
        form.defaults();
      }

      applyLocked.value = true;
      setTimeout(() => (applyLocked.value = false), APPLY_DELAY);
      await nextTick();
      reviewHeading.value?.focus();
    } catch (exception: any) {
      if (request?.signal.aborted) {
        return;
      }

      const responseErrors: Record<string, string[]> =
        exception?.response?.data?.errors ?? {};
      errors.value = Object.entries(responseErrors).map(([path, messages]) => ({
        path: path.split('.'),
        messages,
      }));
      error.value =
        exception?.response?.data?.message || t('A server error occurred.');

      await nextTick();
      focusPrompt('[aria-invalid="true"]');
    } finally {
      form.processing = false;
      request = null;
    }
  }

  async function tryAgain(): Promise<void> {
    step.value = 'prompt';
    form.reviewing = false;
    error.value = null;

    // The Try Again button is gone, so focus moves to the prompt it returns to.
    await nextTick();
    focusPrompt();
  }

  /** Focuses the prompt step's first form control, or the first one matching `filter`. */
  function focusPrompt(filter = ''): void {
    promptBody.value
      ?.querySelector<HTMLElement>(
        ['textarea', 'input', 'select']
          .map((control) => `${control}${filter}`)
          .join(', ')
      )
      ?.focus();
  }

  function toggle(uid: string, checked: boolean): void {
    const next = new Set(selected.value);

    if (checked) {
      next.add(uid);
    } else {
      next.delete(uid);
    }

    selected.value = next;
  }

  function apply(): void {
    if (applyLocked.value) {
      return;
    }

    const accepted = proposals.value.filter((proposal) =>
      selected.value.has(proposal.layoutElementUid)
    );

    if (!accepted.length) {
      error.value = t('Choose at least one change to apply.');

      return;
    }

    context.onApply(accepted);
    slideout?.close({force: true});
  }

  function checkboxValue(event: Event): boolean {
    return Boolean((event.target as {checked?: boolean} | null)?.checked);
  }
</script>

<template>
  <CpContainer>
    <div class="flex flex-col gap-lg py-lg">
      <div role="alert">
        <craft-callout v-if="error" variant="danger">{{ error }}</craft-callout>
      </div>

      <craft-spinner v-if="!promptForm" :label="t('Loading')"></craft-spinner>

      <div v-else v-show="step === 'prompt'" ref="promptBody">
        <craft-field-group>
          <UiRenderer
            ref="renderer"
            :payload="promptForm.ui"
            :errors="errors"
            @change="onPromptChange"
          />
        </craft-field-group>
      </div>

      <section v-if="step === 'review'" class="flex flex-col gap-lg">
        <h2 ref="reviewHeading" tabindex="-1" class="text-lg font-bold">
          {{ hasProposals ? t('Proposed changes') : t('Review') }}
        </h2>

        <craft-callout v-if="summary" icon="sparkles">
          <span class="whitespace-pre-line" v-text="summary"></span>
        </craft-callout>

        <p v-if="!hasProposals && !summary && !findings.length">
          {{ t('Nothing was changed. Try again with a different prompt.') }}
        </p>

        <craft-card
          v-for="{proposal, ...card} in cards"
          :key="proposal.layoutElementUid"
        >
          <craft-checkbox
            v-if="!isFieldLevel"
            slot="label"
            .checked="selected.has(proposal.layoutElementUid)"
            @model-value-changed="
              toggle(proposal.layoutElementUid, checkboxValue($event))
            "
          >
            <label slot="label" class="font-medium">{{ proposal.label }}</label>
          </craft-checkbox>
          <span v-else slot="label" class="font-medium">{{
            proposal.label
          }}</span>

          <craft-badge
            v-if="card.badge"
            slot="badge"
            :fill="proposal.diff?.current ? 'neutral' : 'success'"
            size="small"
            no-prefix
          >
            {{ card.badge }}
          </craft-badge>

          <craft-button
            v-if="card.collapsible"
            slot="actions"
            type="button"
            variant="plain"
            size="small"
            :aria-expanded="
              expandedFields.has(proposal.layoutElementUid) ? 'true' : 'false'
            "
            @click="toggleExpanded(proposal.layoutElementUid)"
          >
            {{
              expandedFields.has(proposal.layoutElementUid)
                ? t('Show changes only')
                : t('Show full field')
            }}
            <span class="sr-only">{{ proposal.label }}</span>
          </craft-button>

          <AskAiDiff
            v-if="proposal.diff"
            :current="proposal.diff.current"
            :proposed="proposal.diff.proposed"
            :quotes="card.quotes"
            :expanded="expandedFields.has(proposal.layoutElementUid)"
          />

          <div v-else-if="proposal.preview" class="grid gap-lg md:grid-cols-2">
            <div class="min-w-0">
              <h3 class="mb-md text-sm font-medium">
                {{ t('Current') }}
                <span class="sr-only">{{ proposal.label }}</span>
              </h3>
              <UiRenderer :payload="proposal.preview.current" />
            </div>
            <div class="min-w-0">
              <h3 class="mb-md text-sm font-medium">
                {{ t('Proposed') }}
                <span class="sr-only">{{ proposal.label }}</span>
              </h3>
              <UiRenderer :payload="proposal.preview.proposed" />
            </div>
          </div>

          <AskAiFindings
            v-if="card.findings.length"
            slot="footer"
            :findings="card.findings"
          />
        </craft-card>

        <craft-card v-for="group in otherFindingGroups" :key="group.key">
          <span slot="label" class="font-medium">{{ group.label }}</span>
          <craft-badge slot="badge" fill="warning" size="small" no-prefix>
            {{ notesLabel(group.findings.length) }}
          </craft-badge>

          <AskAiFindings :findings="group.findings" />
        </craft-card>
      </section>
    </div>

    <LayoutSlot v-if="step === 'review'" name="additional-buttons">
      <craft-button type="button" variant="outline" @click="void tryAgain()">
        {{ t('Try again') }}
      </craft-button>
    </LayoutSlot>
  </CpContainer>
</template>
