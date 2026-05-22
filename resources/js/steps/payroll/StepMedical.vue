<script setup lang="ts">
import { computed } from "vue";

/* -------------------------
  TYPES
--------------------------*/
type Cutoff = {
  id: number;
  cutOffStart: string;
  cutOffEnd: string;
  payrollDate: string;
};

type MedicalRow = {
  id: number;
  empnum: string;
  empname: string;
  total_amount: number;
  processed_at: string | null;
};

/* -------------------------
  PROPS
--------------------------*/
const props = defineProps<{
  cutoffId: number | null;
  cutoffs: Cutoff[];

  medicalRows?: MedicalRow[];
}>();

/* -------------------------
  EMITS
--------------------------*/
const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "run"): void;
  (e: "skip"): void;
  (e: "next"): void;
}>();

/* -------------------------
  COMPUTED
--------------------------*/
const rows = computed(() => props.medicalRows ?? []);
const hasData = computed(() => rows.value.length > 0);

// ✅ get latest processed date
const processedAt = computed(() => {
  if (!rows.value.length) return null;
  return rows.value[0]?.processed_at ?? null;
});

/* -------------------------
  HANDLERS
--------------------------*/
function runProcess() {
  if (!props.cutoffId) {
    alert("Please select cutoff first.");
    return;
  }

  emit("run");
}

function skipStep() {
  emit("skip");
}

function goNext() {
  emit("next");
}

function formatISOToDate(dateTime: string | null | undefined) {
  if (!dateTime) return "—";
  const [date] = dateTime.split("T");
  const [y, m, d] = date.split("-");
  return `${m}-${d}-${y}`;
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <h2 class="text-xl font-bold">Step 4: Process Medical Re-imbursement</h2>

    <!-- ================= WARNING ================= -->
    <div v-if="!cutoffId" class="text-red-500 font-semibold">
      Please select a payroll cutoff first.
    </div>

    <!-- ================= CONTENT ================= -->
    <div v-else class="space-y-4">
      <!-- ACTIONS -->
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold">Approved Medical Reimbursements</h3>

        <div class="flex gap-3">
          <button
            @click="runProcess"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            {{ hasData ? "Re-run Process" : "Run Medical Retrieval" }}
          </button>

          <button
            @click="skipStep"
            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
          >
            Skip
          </button>

          <button @click="goNext" class="px-4 py-2 bg-green-600 text-white rounded">
            Next
          </button>
        </div>
      </div>

      <!-- LAST PROCESSED -->
      <div v-if="processedAt" class="text-green-600">
        Last processed: {{ formatISOToDate(processedAt) }}
      </div>

      <!-- ================= TABLE ================= -->
      <div v-if="hasData" class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-3 py-2 border">Emp #</th>
              <th class="px-3 py-2 border">Employee Name</th>
              <th class="px-3 py-2 border text-right">Total Amount</th>
              <th class="px-3 py-2 border">Processed At</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in rows"
              :key="row.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-3 py-2 border">{{ row.empnum }}</td>
              <td class="px-3 py-2 border">{{ row.empname }}</td>
              <td class="px-3 py-2 border text-right">
                {{
                  Number(row.total_amount).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                  })
                }}
              </td>
              <td class="px-3 py-2 border">
                {{ formatISOToDate(row.processed_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY -->
      <div v-else class="text-gray-500">
        No approved medical reimbursements for this cutoff.
      </div>
    </div>
  </div>
</template>
