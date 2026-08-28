<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import UserThumbnail from '@/common/components/UserThumbnail.vue';
  import {computed} from 'vue';
  import UsersController from '@actions/Users/UsersController';

  const {currentUser} = useCraftData();

  const primaryText = computed(() => {
    if (currentUser!.name !== currentUser!.username) {
      return currentUser!.name;
    }

    return currentUser!.username;
  });

  const secondaryText = computed(() => {
    if (currentUser!.username === currentUser!.name) {
      return currentUser!.email;
    }

    return currentUser!.username;
  });
</script>

<template>
  <craft-action-item
    :href="UsersController.edit['/{cpTrigger?}/myaccount']().url"
  >
    <div class="cp:flex cp:items-center cp:gap-3">
      <UserThumbnail size="md" />
      <div>
        <div class="cp:font-bold">{{ primaryText }}</div>
        <div v-if="secondaryText !== primaryText" class="cp:text-xs">
          {{ secondaryText }}
        </div>
      </div>
    </div>
  </craft-action-item>
</template>

<style scoped lang="scss"></style>
