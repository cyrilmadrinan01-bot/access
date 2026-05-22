<script setup lang="ts">
import { computed, ref } from "vue";

/* -------------------------
  TYPES
--------------------------*/
type Cutoff = {
  id: number;
  cutOffStart: string;
  cutOffEnd: string;
  payrollDate: string;
};

type DeductionRow = {
  id: number;
  empnum: string;
  empname: string;
  deduction_type: string;
  amount: number;
};

/* -------------------------
  PROPS
--------------------------*/
const props = defineProps<{
  cutoffId: number | null;
  cutoffs: Cutoff[];

  employeeDeductionRows?: DeductionRow[];
  employeeDeductionUploadedAt?: string | null;
}>();

/* -------------------------
  EMITS
--------------------------*/
const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "run", payload: { file: File }): void;
  (e: "skip"): void;
  (e: "next"): void;
}>();

/* -------------------------
  STATE
--------------------------*/
const selectedFile = ref<File | null>(null);

/* -------------------------
  COMPUTED
--------------------------*/
const hasData = computed(() => (props.employeeDeductionRows?.length ?? 0) > 0);

/* -------------------------
  HANDLERS
--------------------------*/
function handleFileUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  if (input.files?.length) {
    selectedFile.value = input.files[0];
  }
}

function uploadDeduction() {
  if (!selectedFile.value) {
    alert("Please select a file.");
    return;
  }

  emit("run", { file: selectedFile.value });

  // ✅ Same behavior as StepOtherIncome
  emit("next");
}
function goNext() {
  emit("next");
}

function skipStep() {
  emit("skip");
  emit("next"); // ✅ move forward after skip
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
    <h2 class="text-xl font-bold">Step 3: Upload Employee Deductions</h2>

    <!-- ================= FORM ================= -->
    <div class="space-y-4 max-w-md">
      <div class="flex flex-col gap-2">
        <label class="font-semibold">Upload Excel File</label>
        <input
          type="file"
          accept=".xlsx,.xls"
          @change="handleFileUpload"
          class="border rounded px-3 py-2 dark:bg-gray-800 dark:border-gray-700"
        />
      </div>

      <div class="flex gap-3 items-center flex-wrap">
        <button
          @click="uploadDeduction"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          {{ hasData ? "Re-upload & Save" : "Upload & Save" }}
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

        <div v-if="employeeDeductionUploadedAt" class="text-green-600 font-semibold">
          Last uploaded:
          {{ formatISOToDate(employeeDeductionUploadedAt) }}
        </div>
      </div>
    </div>

    <!-- ================= TABLE ================= -->
    <div v-if="(employeeDeductionRows ?? []).length > 0">
      <h2 class="text-xl font-semibold mb-2">Employee Deductions</h2>

      <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-3 py-2 border">Emp #</th>
              <th class="px-3 py-2 border">Employee Name</th>
              <th class="px-3 py-2 border">Deduction Type</th>
              <th class="px-3 py-2 border text-right">Amount</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in employeeDeductionRows ?? []"
              :key="row.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-3 py-2 border">{{ row.empnum }}</td>
              <td class="px-3 py-2 border">{{ row.empname }}</td>
              <td class="px-3 py-2 border">{{ row.deduction_type }}</td>
              <td class="px-3 py-2 border text-right">
                {{
                  Number(row.amount).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                  })
                }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- EMPTY STATE -->
    <div v-else class="text-gray-500 italic">No deduction data uploaded yet.</div>
  </div>
</template>
