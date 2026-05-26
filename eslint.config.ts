import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginVue from 'eslint-plugin-vue';

export default tseslint.config(
  {
    ignores: [
      'resources/build/**',
      'resources/legacy/**',
      // Fixture file uses PHP-style escaped strings (\/); not real JS issues
      'resources/js/**/fixtures/**',
    ],
  },
  eslint.configs.recommended,
  ...tseslint.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  {
    files: ['resources/js/**/*.{ts,vue}'],
    languageOptions: {
      globals: {
        // Craft CMS globals injected at runtime
        Craft: 'readonly',
        Garnish: 'readonly',
      },
      parserOptions: {
        parser: tseslint.parser,
        extraFileExtensions: ['.vue'],
        sourceType: 'module',
      },
    },
    rules: {
      // Downgrade to warn — widespread in the codebase, address in follow-up
      '@typescript-eslint/no-explicit-any': 'warn',

      // Downgrade to warn — many unused imports exist; clean up in follow-up PRs
      '@typescript-eslint/no-unused-vars': [
        'warn',
        {
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
          caughtErrorsIgnorePattern: '^_',
        },
      ],
      'vue/no-unused-vars': 'warn',

      // Vue single-word component names are intentional in this codebase
      'vue/multi-word-component-names': 'off',

      // Craft uses both Vue components (CamelCase) and web components (kebab-case).
      // Web components require slot="name" (HTML standard), not #name (Vue syntax).
      // eslint-plugin-vue cannot distinguish the two, so this rule is disabled.
      'vue/no-deprecated-slot-attribute': 'off',

      // v-html is used intentionally; XSS risk is accepted in Craft's admin context
      'vue/no-v-html': 'warn',

      // v-html on Craft web components (custom elements) is intentional — they render slot content as HTML
      'vue/no-v-text-v-html-on-component': 'warn',

      // Formatting rules — Prettier handles these
      'vue/html-self-closing': 'off',
      'vue/max-attributes-per-line': 'off',
      'vue/attributes-order': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/multiline-html-element-content-newline': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/first-attribute-linebreak': 'off',
    },
  },
);
