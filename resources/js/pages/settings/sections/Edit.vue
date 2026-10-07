<script setup lang="ts">
  import type {UrlMethodPair} from '@inertiajs/core';
  import {toUriFormat} from '@craftcms/ui';
  import {ref} from 'vue';
  import type {
    UiChange,
    UiChangeKind,
    UiPayload,
    UiValue,
  } from '@/modules/ui/types';
  import {isRecord, pathsMatch} from '@/modules/ui/runtime';
