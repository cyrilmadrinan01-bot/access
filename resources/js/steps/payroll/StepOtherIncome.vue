<script setup lang="ts">
import { computed, ref } from "vue";

/**
 * TYPES
 */
type Cutoff = {
  id: number;
  cutOffStart: string;
  cutOffEnd: string;
  payrollDate: string;
};

type OtherIncomeRow = {
  id: number;
  empnum: string;
  empname: string;
  income_type: string;
  amount: number;
  is_taxable: number;
};

/**
 * PROPS
 */
const props = defineProps<{
  cutoffId: number | null;
  cutoffs: Cutoff[];
  otherIncomeRows?: OtherIncomeRow[];
  otherIncomeUploadedAt?: string | null;
  hasOtherIncomeData?: boolean;
}>();

/**
 * EMITS
 */
const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "run", payload: { file: File; is_taxable: number }): void;
  (e: "skip"): void;
  (e: "next"): void;
}>();

/**
 * STATE
 */
const isTaxable = ref<number>(1);
const selectedFile = ref<File | null>(null);

/**
 * v-model cutoff
 */
const localCutoff = computed({
  get: () => props.cutoffId,
  set: (val) => emit("update:cutoffId", val ? Number(val) : null),
});

/**
 * COMPUTED
 */
const hasData = computed(() => (props.otherIncomeRows?.length ?? 0) > 0);

/**
 * HANDLERS
 */
function handleFileUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  if (input.files?.length) {
    selectedFile.value = input.files[0];
  }
}

/**
 * UPLOAD
 */
function uploadOtherIncome() {
  if (!selectedFile.value) {
    alert("Please select a file.");
    return;
  }

  emit("run", {
    file: selectedFile.value,
    is_taxable: isTaxable.value,
  });
}

/**
 * NEXT
 */
function goNext() {
  emit("next");
}

/**
 * SKIP
 */
function skipStep() {
  emit("skip");
}

/**
 * FORMAT DATE
 */
function formatISOToDate(dateTime: string | null | undefined) {
  if (!dateTime) return "—";
  const [date] = dateTime.split("T");
  const [y, m, d] = date.split("-");
  return `${m}-${d}-${y}`;
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <h2 class="text-xl font-bold">Step 2: Upload Other Income</h2>

    <!-- FORM -->
    <div class="space-y-4 max-w-md">
      <!-- TAX TYPE -->
      <div class="flex flex-col gap-2">
        <label class="font-semibold">Tax Type</label>
        <select
          v-model="isTaxable"
          class="border rounded px-3 py-2 dark:bg-gray-800 dark:border-gray-700"
        >
          <option :value="1">Taxable</option>
          <option :value="0">Non-Taxable</option>
        </select>
      </div>

      <!-- FILE -->
      <div class="flex flex-col gap-2">
        <label class="font-semibold">Upload Excel File</label>
        <input
          type="file"
          accept=".xlsx,.xls"
          @change="handleFileUpload"
          class="border rounded px-3 py-2 dark:bg-gray-800 dark:border-gray-700"
        />
      </div>

      <!-- ACTIONS -->
      <div class="flex gap-3 items-center flex-wrap">
        <button
          @click="uploadOtherIncome"
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

        <div v-if="otherIncomeUploadedAt" class="text-green-600 font-semibold">
          Last uploaded:
          {{ formatISOToDate(otherIncomeUploadedAt) }}
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div v-if="otherIncomeRows?.length">
      <h2 class="text-xl font-semibold mb-2">Other Income Summary</h2>

      <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-3 py-2 border">Emp #</th>
              <th class="px-3 py-2 border">Employee Name</th>
              <th class="px-3 py-2 border">Income Type</th>
              <th class="px-3 py-2 border">Amount</th>
              <th class="px-3 py-2 border">Taxable</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in otherIncomeRows"
              :key="row.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-3 py-2 border">{{ row.empnum }}</td>
              <td class="px-3 py-2 border">{{ row.empname }}</td>
              <td class="px-3 py-2 border">{{ row.income_type }}</td>
              <td class="px-3 py-2 border">
                {{ Number(row.amount).toFixed(2) }}
              </td>
              <td class="px-3 py-2 border">
                {{ row.is_taxable ? "Yes" : "No" }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="text-gray-500">No other income data yet.</div>
  </div>
</template>
