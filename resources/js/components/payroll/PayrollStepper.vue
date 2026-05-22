<script setup lang="ts">
defineProps<{
  steps: any[];
  activeStep: number;
  stepsStatus: Record<string, boolean>;
}>();

const emit = defineEmits(["change"]);
</script>

<template>
  <div class="flex border-b mb-4">
    <button
      v-for="(step, index) in steps"
      :key="step.key"
      @click="emit('change', index)"
      class="px-4 py-2 border-b-2"
      :class="activeStep === index ? 'border-blue-600 font-bold' : ''"
    >
      {{ step.label }}
      <span v-if="stepsStatus[step.key]" class="text-green-600">✔</span>
    </button>
  </div>
</template>
