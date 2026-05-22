<script setup lang="ts">
import { Pencil } from "lucide-vue-next";

defineProps<{
  title: string;
  canEdit?: boolean;
}>();

const emit = defineEmits<{
  (e: "edit"): void;
}>();
</script>

<template>
  <h2
    class="flex items-center justify-between bg-primary/20 text-primary font-bold text-xl p-3 rounded shadow-sm"
  >
    <span>{{ title }}</span>

    <button
      v-if="canEdit"
      @click.stop="emit('edit')"
      class="rounded-md p-1.5 hover:bg-primary/30 transition"
      title="Edit"
      aria-label="Edit section"
    >
      <Pencil class="h-4 w-4" />
    </button>
  </h2>
</template>
