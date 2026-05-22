<script setup lang="ts">
defineProps<{
  title: string;
  canEdit: boolean;
}>();

defineEmits(["edit"]);
</script>

<template>
  <div class="flex items-center justify-between">
    <h2 class="text-lg font-semibold">{{ title }}</h2>

    <button
      v-if="canEdit"
      @click="$emit('edit')"
      class="text-sm text-primary hover:underline"
    >
      Edit
    </button>
  </div>
</template>
