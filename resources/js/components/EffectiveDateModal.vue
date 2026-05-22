<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  show: boolean;
  employeeId: number;
  section: string;
  fields: Record<string, any>;
}>();

const emit = defineEmits(["close"]);

const effectiveDate = ref("");
const formData = ref({ ...props.fields });

const save = () => {
  router.post(
    route("employees.effective.store", {
      employee: props.employeeId,
      section: props.section,
    }),
    {
      effective_date: effectiveDate.value,
      data: formData.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => emit("close"),
    }
  );
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 w-full max-w-lg">
      <h2 class="text-lg font-semibold mb-4">Edit {{ section }}</h2>

      <div class="mb-4">
        <label class="text-sm">Effective Date</label>
        <input type="date" v-model="effectiveDate" class="w-full border rounded p-2" />
      </div>

      <div v-for="(value, key) in formData" :key="key" class="mb-3">
        <label class="text-sm">{{ key }}</label>
        <input v-model="formData[key]" class="w-full border rounded p-2" />
      </div>

      <div class="flex justify-end gap-2">
        <button @click="emit('close')" class="px-3 py-2 border rounded">Cancel</button>
        <button @click="save" class="px-3 py-2 bg-primary text-white rounded">
          Save
        </button>
      </div>
    </div>
  </div>
</template>
