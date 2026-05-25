<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";

defineProps<{
  payslips: any;
}>();

const downloadPdf = (id: number) => {
  window.open(`/employees/payslips/${id}/download`, "_blank");
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString("en-PH", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};
</script>

<template>
  <AppLayout title="My Payslips">
    <div class="p-6">
      <div
        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm"
      >
        <!-- HEADER -->
        <div
          class="flex items-center justify-between px-6 py-5 border-b border-gray-200 dark:border-gray-700"
        >
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              My Payslips
            </h1>

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              View and download your payroll payslips
            </p>
          </div>
        </div>

        <!-- TABLE -->
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead
              class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            >
              <tr>
                <th
                  class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300"
                >
                  Payroll Date
                </th>

                <th
                  class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300"
                >
                  Employee
                </th>

                <th
                  class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300"
                >
                  Gross Pay
                </th>

                <th
                  class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300"
                >
                  Net Pay
                </th>

                <th
                  class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300"
                >
                  Action
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="row in payslips.data"
                :key="row.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
              >
                <!-- PAYROLL DATE -->
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
                >
                  {{ formatDate(row.payrollDate) }}
                </td>

                <!-- EMPLOYEE -->
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
                >
                  {{ row.empname }}
                </td>

                <!-- GROSS -->
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100"
                >
                  ₱{{ Number(row.gross_pay).toLocaleString() }}
                </td>

                <!-- NET -->
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-green-600 dark:text-green-400"
                >
                  ₱{{ Number(row.net_pay).toLocaleString() }}
                </td>

                <!-- ACTION -->
                <td class="px-6 py-4 text-center">
                  <button
                    @click="downloadPdf(row.id)"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-sm font-medium transition"
                  >
                    Download PDF
                  </button>
                </td>
              </tr>

              <!-- EMPTY -->
              <tr v-if="!payslips.data.length">
                <td
                  colspan="5"
                  class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                  No payslips available.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
