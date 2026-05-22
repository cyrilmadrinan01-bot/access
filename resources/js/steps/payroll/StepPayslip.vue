<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  cutoffId: number | null;
  isLocked?: boolean;
  payrollPayslipRows?: any[];
}>();

const emit = defineEmits(["update:cutoffId", "next", "reload", "lock"]);

const loading = ref(false);

const locked = computed(() => props.isLocked === true);

function money(value: any) {
  return Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function generatePayslip() {
  if (!props.cutoffId) {
    alert("Please select cutoff first.");
    return;
  }

  loading.value = true;

  router.post(
    route("payroll.payslip.generate", props.cutoffId),
    {},
    {
      preserveScroll: true,
      onFinish: () => (loading.value = false),
      onSuccess: () => {
        router.reload({ only: ["payrollPayslipRows"] });
      },
    }
  );
}

function lockStep() {
  if (confirm("Lock payslip step?")) {
    emit("lock");
  }
}

function goNext() {
  emit("next");
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold">Step 9: Payslip Generation</h2>

    <!-- ACTIONS -->
    <div class="flex gap-3">
      <template v-if="!locked">
        <button
          @click="generatePayslip"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded"
        >
          {{ loading ? "Generating..." : "Generate Payslip" }}
        </button>

        <button @click="lockStep" class="px-4 py-2 bg-red-600 text-white rounded">
          Lock
        </button>
      </template>

      <template v-else>
        <button @click="goNext" class="px-4 py-2 bg-green-600 text-white rounded">
          Next
        </button>
      </template>
    </div>

    <!-- TABLE -->
    <div
      v-if="payrollPayslipRows?.length"
      class="mt-6 w-full overflow-auto max-h-[600px] rounded-xl border"
    >
      <table class="min-w-full text-sm border">
        <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-20">
          <tr>
            <th class="px-3 py-2 text-left">Emp #</th>
            <th class="px-3 py-2 text-left">Name</th>
            <th class="px-3 py-2 text-right">Gross</th>
            <th class="px-3 py-2 text-right">Income</th>
            <th class="px-3 py-2 text-right">Withholding Tax</th>
            <th class="px-3 py-2 text-right">Deduction</th>
            <th class="px-3 py-2 text-right font-bold">Net</th>
            <th class="px-3 py-2 text-right">YTD Gross</th>
            <th class="px-3 py-2 text-right">YTD Tax</th>
            <th class="px-3 py-2 text-right">YTD Net</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="row in payrollPayslipRows" :key="row.id">
            <td class="px-3 py-2">{{ row.empnum }}</td>
            <td class="px-3 py-2">{{ row.empname }}</td>

            <td class="px-3 py-2 text-right">{{ money(row.gross_pay) }}</td>
            <td class="px-3 py-2 text-right text-blue-600">
              {{ money(row.total_income) }}
            </td>
            <td class="px-3 py-2 text-right text-white-600">
              {{ money(row.employeeTax) }}
            </td>
            <td class="px-3 py-2 text-right text-red-600">
              {{ money(row.total_deduction) }}
            </td>
            <td class="px-3 py-2 text-right font-bold text-green-600">
              {{ money(row.net_pay) }}
            </td>

            <td class="px-3 py-2 text-right">
              {{ money(row.ytd_gross) }}
            </td>
            <td class="px-3 py-2 text-right text-red-500">
              {{ money(row.ytd_tax) }}
            </td>
            <td class="px-3 py-2 text-right">
              {{ money(row.ytd_net) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="text-gray-500">No payslip data available.</div>
  </div>
</template>
