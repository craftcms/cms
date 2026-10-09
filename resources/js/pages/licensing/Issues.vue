<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {sendApiRequest} from '@craftcms/ui/utilities/api/apiClient';
  import {http} from '@craftcms/ui/utilities/api/http';
  import {computed, onBeforeUnmount, onMounted, ref} from 'vue';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import {setShunCookie} from '@actions/App/LicensesController';

  defineOptions({layout: []});

  const props = defineProps<{
    issues: string[];
    hash: string;
    cartUrl: string;
    duration: number;
  }>();

  const trialHtml = t('or <a href="{url}">try before buying</a>', {
    url: 'https://craftcms.com/knowledge-base/how-to-trial-craft-cms-and-plugin-editions',
  });

  // ICU messages close with `}}`, which a template interpolation can't hold.
  const intro = t(
    'The following licensing {total, plural, =1{issue} other{issues}} can be resolved with a single purchase on Craft Console:',
    {total: props.issues.length}
  );

  const remaining = ref(props.duration);
  const countdown = computed(() =>
    t(
      'Continue to the control panel in {duration, number} {duration, plural, =1{second} other{seconds}}…',
      {duration: remaining.value}
    )
  );
  const canContinue = ref(false);
  const refreshing = ref(false);
  let timer: ReturnType<typeof setInterval> | undefined;

  async function refresh(): Promise<void> {
    refreshing.value = true;

    try {
      await sendApiRequest('GET', 'ping');
      window.location.reload();
    } finally {
      refreshing.value = false;
    }
  }

  // Lets the user through for the rest of the day once they've waited.
  async function allowContinue(): Promise<void> {
    await http.post(setShunCookie().url, {hash: props.hash});
    canContinue.value = true;
  }

  function tick(): void {
    if (remaining.value > 0) {
      return;
    }

    clearInterval(timer);
    allowContinue();
  }

  onMounted(() => {
    timer = setInterval(() => {
      remaining.value--;
      tick();
    }, 1000);
    tick();
  });

  onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
  <AuthBase :title="t('License purchase required.')">
    <craft-pane>
      <div class="grid gap-lg">
        <h2>{{ t('License purchase required.') }}</h2>

        <p>{{ intro }}</p>

        <ul class="list-disc ps-lg">
          <li v-for="issue in issues" :key="issue">{{ issue }}</li>
        </ul>

        <div class="flex flex-wrap items-center justify-between gap-lg">
          <div class="flex flex-wrap items-center gap-md">
            <craft-button variant="primary" :href="cartUrl" target="_blank">
              {{ t('Resolve now') }}
            </craft-button>
            <span v-html="trialHtml"></span>
          </div>

          <craft-button
            icon="refresh"
            variant="outline"
            :loading="refreshing"
            @click="refresh"
          >
            {{ t('Refresh') }}
          </craft-button>
        </div>
      </div>
    </craft-pane>

    <p v-if="canContinue" class="mt-md text-center" aria-live="polite">
      <a :href="$page.url">{{ t('Continue to the control panel') }}</a>
    </p>
    <p v-else class="mt-md text-center text-sm opacity-75" role="timer">
      {{ countdown }}
    </p>
  </AuthBase>
</template>
