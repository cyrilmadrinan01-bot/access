<script setup lang="ts">
defineProps<{
  steps: { key: string; status: string }[];
  progress: number;
}>();

function statusColor(status: string) {
  if (status === "completed") return "bg-green-500";
  if (status === "processing") return "bg-blue-500";
  if (status === "failed") return "bg-red-500";
  if (status === "skipped") return "bg-gray-400";
  return "bg-gray-300";
}
</script>

<template>
  <div class="space-y-4">
    <!-- Progress Bar -->
    <div class="w-full bg-gray-200 rounded h-4">
      <div
        class="bg-blue-600 h-4 rounded transition-all"
        :style="{ width: progress + '%' }"
      ></div>
    </div>

    <div class="text-sm font-semibold">Progress: {{ progress }}%</div>

    <!-- Steps -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
      <div
        v-for="step in steps"
        :key="step.key"
        class="p-2 rounded text-white text-xs text-center"
        :class="statusColor(step.status)"
      >
        {{ step.key }}
        <div class="text-[10px] uppercase">{{ step.status }}</div>
      </div>
    </div>
  </div>
</template>
