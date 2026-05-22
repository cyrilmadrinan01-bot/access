<!-- resources/js/steps/payroll/StepPhilhealth.vue -->
<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  cutoffId: number | null;

  philhealthRows?: {
    id: number;
    empnum: string;
    empname: string;
    philhealth_number: string;
    year: string;
    month: string;
    employee: string;
    employer: string;
    processed_at: string | null;
  }[];
}>();

const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "next"): void;
  (e: "reload"): void;
  (e: "skip"): void;
}>();

const year = ref(new Date().getFullYear());
const month = ref(new Date().getMonth() + 1);

function processPhilHealth() {
  if (!props.cutoffId) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("philhealth.run.process", props.cutoffId),
    {
      year: year.value,
      month: month.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        emit("next");
        emit("reload");
      },
    }
  );
}

function skip() {
  if (!props.cutoffId) return;

  router.post(
    route("admin.payroll.philhealth.skip", {
      cutoff: props.cutoffId,
    }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        emit("next");
        emit("reload");
      },
    }
  );
}

function goNext() {
  emit("next");
}

function skipStep() {
  emit("skip");
  emit("next"); // ✅ move forward after skip
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold">Step 7: Generate PhilHealth Contribution</h2>

    <!-- Controls -->
    <div class="flex gap-4 items-end flex-wrap">
      <div class="flex flex-col">
        <label class="font-semibold">Year</label>
        <input
          v-model="year"
          type="number"
          class="border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
        />
      </div>

      <div class="flex flex-col">
        <label class="font-semibold">Month</label>

        <select
          v-model="month"
          class="border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
        >
          <option :value="1">January</option>
          <option :value="2">February</option>
          <option :value="3">March</option>
          <option :value="4">April</option>
          <option :value="5">May</option>
          <option :value="6">June</option>
          <option :value="7">July</option>
          <option :value="8">August</option>
          <option :value="9">September</option>
          <option :value="10">October</option>
          <option :value="11">November</option>
          <option :value="12">December</option>
        </select>
      </div>

      <button
        @click="processPhilHealth"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Generate PhilHealth Contribution
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

    <!-- Table -->
    <div v-if="philhealthRows?.length" class="mt-6">
      <h2 class="text-xl font-semibold mb-3">Generated PhilHealth Contributions</h2>

      <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-3 py-2 border">Emp #</th>
              <th class="px-3 py-2 border">Employee Name</th>
              <th class="px-3 py-2 border">PhilHealth No.</th>
              <th class="px-3 py-2 border text-right">Employee Share</th>
              <th class="px-3 py-2 border text-right">Employer Share</th>
              <th class="px-3 py-2 border text-right font-semibold">Total</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in philhealthRows"
              :key="row.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-3 py-2 border">{{ row.empnum }}</td>
              <td class="px-3 py-2 border">{{ row.empname }}</td>
              <td class="px-3 py-2 border">{{ row.philhealth_number }}</td>

              <td class="px-3 py-2 border text-right">
                {{ Number(row.employee).toFixed(2) }}
              </td>

              <td class="px-3 py-2 border text-right">
                {{ Number(row.employer).toFixed(2) }}
              </td>

              <td class="px-3 py-2 border text-right font-semibold text-blue-600">
                {{ (Number(row.employee) + Number(row.employer)).toFixed(2) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="text-gray-500 mt-4">
      No PhilHealth contributions generated yet.
    </div>
  </div>
</template>
