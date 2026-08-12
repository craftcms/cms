<script setup lang="ts">
  import {capitalize} from '@craftcms/ui';
  import type {PluginInfo} from '@/modules/plugin-manager/types/plugins';
  import {computed} from 'vue';
  import Text from '@/common/components/Text.vue';
  import {switchEdition} from '@actions/PluginsController';
  import {Form} from '@inertiajs/vue3';

  const props = defineProps<{
    plugin: PluginInfo;
  }>();

  const editionName = computed(() => capitalize(props.plugin.licensedEdition!));
  const accountLink = computed(
    () =>
      `<a href="https://console.craftcms.com" rel="noopener" target="_blank">console.craftcms.com</a>`
  );
</script>

<template>
  <craft-callout
    v-for="issue in plugin.licenseIssues"
    :key="issue"
    variant="danger"
    appearance="plain"
    class="p-0"
  >
    <div v-if="issue === 'wrong_edition'" class="flex items-center gap-1">
      <Text
        template="This license is for the {name} edition."
        :params="{name: editionName}"
      />
      <Form
        :action="switchEdition({handle: plugin.handle})"
        method="post"
        v-slot="{processing}"
      >
        <input type="hidden" name="edition" :value="plugin.licensedEdition" />
        <craft-button type="submit" inherit :loading="processing"
          >Switch</craft-button
        >
      </Form>
    </div>
    <Text
      v-else-if="issue === 'no_trials'"
      template="Plugin trials are not allowed on this domain."
    />
    <Text
      v-else-if="issue === 'mismatched'"
      template='This license is tied to another Craft install. Visit {accountLink} to detach it, or <a href="{buyUrl}">buy a new license</a>.'
      :params="{accountLink: accountLink, buyUrl: plugin.buyUrl}"
    />
    <Text
      v-else-if="issue === 'astray'"
      template="This license isn’t allowed to run version {version}."
      :params="{version: plugin.version}"
    />
    <Text
      v-else-if="issue === 'required'"
      template="A license key is required."
    />
    <Text v-else template="Your license key is invalid." />
  </craft-callout>
</template>

<style scoped lang="scss"></style>
