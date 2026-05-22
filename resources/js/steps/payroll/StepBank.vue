<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  cutoffId: number | null;
  bankRows?: any[];
}>();

const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "skip"): void;
  (e: "next"): void;
  (e: "reload"): void;
}>();

function generateFile() {
  if (!props.cutoffId) return;

  router.post(
    route("bank.run.process", { cutoff: props.cutoffId }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        emit("reload");
      },
    }
  );
}

function downloadFile() {
  if (!props.cutoffId) return;

  const url = route("bank.download", {
    cutoff: props.cutoffId,
  }).toString(); // ✅ FIXED TS ERROR

  window.open(url, "_blank");
}

function skipStep() {
  emit("skip");
}

function goNext() {
  emit("next");
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold">Step 9: Generate Bank File</h2>

    <div class="flex gap-3">
      <button @click="generateFile" class="px-4 py-2 bg-blue-600 text-white rounded">
        Generate Bank File
      </button>

      <button @click="downloadFile" class="px-4 py-2 bg-green-600 text-white rounded">
        Download CSV
      </button>

      <button
        @click="skipStep"
        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
      >
        Skip
      </button>

      <button
        @click="goNext"
        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
      >
        Next
      </button>
    </div>

    <table v-if="bankRows?.length" class="w-full text-sm border mt-4">
      <thead class="bg-gray-100 dark:bg-gray-800">
        <tr>
          <th class="border px-2 py-2">Account Number</th>
          <th class="border px-2 py-2">Amount</th>
          <th class="border px-2 py-2">Employee Name</th>
          <th class="border px-2 py-2">Reference</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="row in bankRows" :key="row.id">
          <td class="border px-2 py-2">{{ row.account_number }}</td>
          <td class="border px-2 py-2">{{ row.amount }}</td>
          <td class="border px-2 py-2">{{ row.employee_name }}</td>
          <td class="border px-2 py-2">{{ row.reference_number }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
