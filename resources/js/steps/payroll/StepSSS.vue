<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

/* -------------------------
  PROPS
--------------------------*/
const props = defineProps<{
  cutoffId: number | null;

  sssRows?: {
    id: number;
    empnum: string;
    empname: string;
    sss_number: string;
    year: string;
    month: string;
    employee: string;
    employer: string;
    ec: string;
    processed_at: string | null;
  }[];
}>();

/* -------------------------
  EMITS
--------------------------*/
const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "reload"): void;
  (e: "skip"): void;
  (e: "next"): void;
}>();

/* -------------------------
  STATE
--------------------------*/
const sssYear = ref(new Date().getFullYear());
const sssMonth = ref(new Date().getMonth() + 1);

const rows = computed(() => props.sssRows ?? []);
const hasData = computed(() => (props.sssRows?.length ?? 0) > 0);

/* -------------------------
  ACTIONS
--------------------------*/
function processSSS() {
  if (!props.cutoffId) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("sss.run.process", props.cutoffId),
    {
      year: sssYear.value,
      month: sssMonth.value,
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
  emit("next"); // ✅ move forward after skip
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold">Step 5: Generate SSS Contribution</h2>

    <!-- CONTROLS -->
    <div class="flex gap-4 items-end flex-wrap">
      <div class="flex flex-col">
        <label class="font-semibold">Year</label>
        <input v-model="sssYear" type="number" class="border rounded-lg px-3 py-2" />
      </div>

      <div class="flex flex-col">
        <label class="font-semibold">Month</label>
        <select
          v-model="sssMonth"
          class="border rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition duration-150"
        >
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
        @click="processSSS"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Generate SSS Contribution
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
    <div v-if="sssRows?.length" class="mt-6">
      <h3 class="text-lg font-semibold mb-3">Generated SSS Contributions</h3>

      <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-3 py-2 border">Emp #</th>
              <th class="px-3 py-2 border">Employee Name</th>
              <th class="px-3 py-2 border">SSS No.</th>
              <th class="px-3 py-2 border">Month</th>
              <th class="px-3 py-2 border">Year</th>
              <th class="px-3 py-2 border text-right">Employee</th>
              <th class="px-3 py-2 border text-right">Employer</th>
              <th class="px-3 py-2 border text-right">EC</th>
              <th class="px-3 py-2 border text-right font-semibold">Total</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in sssRows"
              :key="row.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
              <td class="px-3 py-2 border">{{ row.empnum }}</td>
              <td class="px-3 py-2 border">{{ row.empname }}</td>
              <td class="px-3 py-2 border">{{ row.sss_number }}</td>
              <td class="px-3 py-2 border">{{ row.month }}</td>
              <td class="px-3 py-2 border">{{ row.year }}</td>

              <td class="px-3 py-2 border text-right">
                {{ Number(row.employee).toFixed(2) }}
              </td>

              <td class="px-3 py-2 border text-right">
                {{ Number(row.employer).toFixed(2) }}
              </td>

              <td class="px-3 py-2 border text-right">
                {{ Number(row.ec).toFixed(2) }}
              </td>

              <td class="px-3 py-2 border text-right font-semibold text-blue-600">
                {{
                  (Number(row.employee) + Number(row.employer) + Number(row.ec)).toFixed(
                    2
                  )
                }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- EMPTY -->
    <div v-else class="text-gray-500">No SSS contributions generated yet.</div>
  </div>
</template>
