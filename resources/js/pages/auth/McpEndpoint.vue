<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import CodeHighlight from '@/common/components/CodeHighlight.vue';

  const props = defineProps<{
    url: string;
    serverName: string;
    isPublic: boolean;
  }>();

  const runInTerminal = t('Run this in your terminal.');
  const terminal = t('Terminal');

  const clients = computed(() => [
    {
      name: 'Claude Code',
      instructions: runInTerminal,
      caption: terminal,
      snippet: `claude mcp add --transport http ${props.serverName} ${props.url}`,
    },
    {
      name: 'Codex',
      instructions: runInTerminal,
      caption: terminal,
      snippet: `codex mcp add ${props.serverName} --url ${props.url}`,
    },
    {
      name: 'Gemini CLI',
      instructions: runInTerminal,
      caption: terminal,
      snippet: `gemini mcp add --transport http ${props.serverName} ${props.url}`,
    },
    {
      name: 'VS Code',
      instructions: runInTerminal,
      caption: terminal,
      snippet: `code --add-mcp '${JSON.stringify({name: props.serverName, type: 'http', url: props.url})}'`,
    },
    {
      name: 'Cursor',
      instructions: t(
        'Add this to .cursor/mcp.json in your project, or to ~/.cursor/mcp.json for all projects.'
      ),
      caption: 'mcp.json',
      language: 'json',
      snippet: JSON.stringify(
        {mcpServers: {[props.serverName]: {url: props.url}}},
        null,
        2
      ),
    },
    {
      name: 'Claude',
      instructions: t(
        'In Claude, open Settings → Connectors, choose “Add custom connector”, and paste the server URL above.'
      ),
      caption: null,
      snippet: null,
    },
  ]);
</script>

<template>
  <AuthBase :title="t('MCP server')" wide>
    <craft-pane class="mcp-endpoint">
      <div class="mcp-endpoint__content">
        <header class="mcp-endpoint__header">
          <p class="mcp-endpoint__eyebrow">{{ t('MCP server') }}</p>
          <h2 class="mcp-endpoint__title">
            {{
              isPublic
                ? t('Ask your AI assistant about this site')
                : t('Manage this site from your AI assistant')
            }}
          </h2>
          <p class="mcp-endpoint__lead">
            {{
              isPublic
                ? t(
                    'This URL is an MCP server for AI assistants, not a web page. Add it to your assistant to search and read this site’s public content in plain language.'
                  )
                : t(
                    'This URL is an MCP server for AI assistants, not a web page. Add it to your assistant to work with this site’s content and settings in plain language.'
                  )
            }}
          </p>
        </header>

        <craft-input-copy .modelValue="url" monospace>
          <label slot="label">{{ t('Server URL') }}</label>
        </craft-input-copy>

        <section
          class="mcp-endpoint__section"
          aria-labelledby="mcp-endpoint-connect"
        >
          <h3 id="mcp-endpoint-connect" class="mcp-endpoint__subtitle">
            {{ t('Connect your assistant') }}
          </h3>

          <craft-tabs :label="t('AI assistants')" sync-location-hash="false">
            <template v-for="client in clients" :key="client.name">
              <craft-tab slot="tab">{{ client.name }}</craft-tab>
              <div slot="panel">
                <div class="mcp-endpoint__panel">
                  <p>{{ client.instructions }}</p>
                  <figure
                    v-if="client.snippet"
                    class="mcp-endpoint__snippet"
                    data-theme="dark"
                  >
                    <figcaption class="mcp-endpoint__snippet-header">
                      <span>{{ client.caption }}</span>
                      <craft-copy-button
                        :value="client.snippet"
                        :tooltip-label="t('Copy')"
                      ></craft-copy-button>
                    </figcaption>
                    <CodeHighlight
                      class="mcp-endpoint__snippet-code"
                      :language="client.language ?? 'shell'"
                      :code="client.snippet"
                    />
                  </figure>
                </div>
              </div>
            </template>
          </craft-tabs>
        </section>

        <p class="mcp-endpoint__note">
          {{
            isPublic
              ? t(
                  'No sign-in is needed. Assistants can only read the content this site has made public.'
                )
              : t(
                  'The first time your assistant connects, it opens a browser window where you sign in and approve access. Your account needs the “Use Craft MCP” permission.'
                )
          }}
        </p>
      </div>
    </craft-pane>
  </AuthBase>
</template>

<style scoped>
  .mcp-endpoint {
    width: 100%;
  }

  .mcp-endpoint__content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-xl);
    padding: var(--c-spacing-md);
  }

  .mcp-endpoint__content p {
    margin: 0;
    overflow-wrap: anywhere;
  }

  .mcp-endpoint__header {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .mcp-endpoint__eyebrow {
    color: var(--c-text-quiet);
    font-size: var(--text-sm);
    font-weight: var(--font-weight-medium);
  }

  .mcp-endpoint__title {
    margin-block: 0 var(--c-spacing-md);
    margin-inline: 0;
    font-size: var(--text-2xl);
    font-weight: var(--font-weight-semibold);
    letter-spacing: var(--tracking-tight);
    line-height: 1.2;
  }

  .mcp-endpoint__lead {
    max-width: 60ch;
    color: var(--c-text-quiet);
  }

  .mcp-endpoint__section {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-sm);
  }

  .mcp-endpoint__subtitle {
    margin: 0;
    font-size: var(--text-lg);
    font-weight: var(--font-weight-semibold);
  }

  .mcp-endpoint__panel {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
    padding-block-start: var(--c-spacing-md);
  }

  .mcp-endpoint__snippet {
    margin: 0;
    overflow: hidden;
    border: 1px solid transparent;
    border-radius: var(--c-radius-lg);
  }

  .mcp-endpoint__snippet-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-md);
    padding-inline: var(--c-spacing-lg) var(--c-spacing-sm);
    padding-block: var(--c-spacing-xs);
    color: var(--c-text-quiet);
    font-family: var(--c-font-mono);
    font-size: var(--text-xs);
    border-block-end: 1px solid currentColor;
    border-block-end-color: var(--c-color-neutral-border-quiet);
  }

  .mcp-endpoint__snippet-header craft-copy-button::part(button) {
    justify-content: center;
    min-inline-size: 1.75rem;
    min-block-size: 1.75rem;
    border-radius: var(--c-radius-sm);
    outline-color: currentColor;
  }

  .mcp-endpoint__snippet-code {
    margin: 0;
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-spacing-lg);
    font-family: var(--c-font-mono);
    font-size: var(--text-sm);
    line-height: 1.6;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
  }

  .mcp-endpoint__note {
    padding-block-start: var(--c-spacing-lg);
    color: var(--c-text-quiet);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  @media (forced-colors: active) {
    .mcp-endpoint__snippet-header {
      border-block-end-color: CanvasText;
    }
  }
</style>
