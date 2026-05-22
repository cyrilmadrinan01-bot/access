<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

/* -------------------------
  PROPS
--------------------------*/
const props = defineProps<{
  cutoffId: number | null;

  pagibigRows?: {
    id: number;
    empnum: string;
    empname: string;
    pagibig_number: string;
    employee: number;
    employer: number;
  }[];
}>();

/* -------------------------
  EMITS
--------------------------*/
const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "next"): void;
  (e: "reload"): void;
  (e: "skip"): void;
}>();

/* -------------------------
  STATE
--------------------------*/
const year = ref(new Date().getFullYear());
const month = ref(new Date().getMonth() + 1);

const rows = computed(() => props.pagibigRows ?? []);
const hasData = computed(() => (props.pagibigRows?.length ?? 0) > 0);

/* -------------------------
  ACTIONS
--------------------------*/
function processPagibig() {
  if (!props.cutoffId) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("pagibig.run.process", props.cutoffId),
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

function goNext() {
  emit("next");
}

function skipStep() {
  emit("skip");
  emit("next");
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold">Step 6: Generate Pag-Ibig Contribution</h2>

    <!-- CONTROLS -->
    <div class="flex gap-4 items-end flex-wrap">
      <div class="flex flex-col">
        <label class="font-semibold">Year</label>
        <input
          v-model="year"
          type="number"
          class="border rounded-lg px-3 py-2 dark:bg-gray-800"
        />
      </div>

      <div class="flex flex-col">
        <label class="font-semibold">Month</label>
        <select v-model="month" class="border rounded-lg px-3 py-2 dark:bg-gray-800">
          <option value="1">January</option>
          <option value="2">February</option>
          <option value="3">March</option>
          <option value="4">April</option>
          <option value="5">May</option>
          <option value="6">June</option>
          <option value="7">July</option>
          <option value="8">August</option>
          <option value="9">September</option>
          <option value="10">October</option>
          <option value="11">November</option>
          <option value="12">December</option>
        </select>
      </div>

      <button
        @click="processPagibig"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Generate Pag-Ibig Contribution
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

    <!-- TABLE -->
    <div v-if="pagibigRows?.length" class="mt-6">
      <h3 class="text-lg font-semibold mb-3">Generated Pag-Ibig Contributions</h3>

      <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-3 py-2 border">Emp #</th>
              <th class="px-3 py-2 border">Employee Name</th>
              <th class="px-3 py-2 border">Pag-Ibig No.</th>
              <th class="px-3 py-2 border text-right">Employee</th>
              <th class="px-3 py-2 border text-right">Employer</th>
              <th class="px-3 py-2 border text-right font-semibold">Total</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in pagibigRows"
              :key="row.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-3 py-2 border">{{ row.empnum }}</td>
              <td class="px-3 py-2 border">{{ row.empname }}</td>
              <td class="px-3 py-2 border">{{ row.pagibig_number }}</td>

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

    <div v-else class="text-gray-500 mt-4">No Pag-Ibig contributions generated yet.</div>
  </div>
</template>
