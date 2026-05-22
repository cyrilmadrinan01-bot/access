<script setup lang="ts">
import { router } from "@inertiajs/vue3";

const props = defineProps<{
  step: string;
  cutoffId: number;
}>();

function retry() {
  router.post(`/payroll/${props.cutoffId}/retry/${props.step}`);
}

function rollback() {
  router.post(`/payroll/${props.cutoffId}/rollback/${props.step}`);
}
</script>

<template>
  <div class="flex gap-2 mt-2">
    <button @click="retry" class="px-2 py-1 bg-yellow-500 text-white rounded text-xs">
      Retry
    </button>

    <button @click="rollback" class="px-2 py-1 bg-red-500 text-white rounded text-xs">
      Rollback
    </button>
  </div>
</template>
