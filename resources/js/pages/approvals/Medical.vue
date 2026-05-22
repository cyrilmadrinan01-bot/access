<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { route } from "ziggy-js";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  pending: any[];
  approved: any[];
  rejected: any[];
}>();

type StatusType = "PENDING" | "APPROVED" | "REJECTED";

const tabs = ["PENDING", "APPROVED", "REJECTED"] as const;
const activeTab = ref<StatusType>("PENDING");

function statusBadge(status: StatusType) {
  switch (status) {
    case "PENDING":
      return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300";
    case "APPROVED":
      return "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300";
    case "REJECTED":
      return "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300";
  }
}

function approve(id: number) {
  router.post(route("medical.approve", id));
}

function reject(id: number) {
  const note = prompt("Enter rejection reason:");
  if (!note) return;

  router.post(route("medical.reject", id), {
    adminNotes: note,
  });
}

function cancel(id: number) {
  if (!confirm("Are you sure you want to cancel this decision?")) return;
  router.post(route("medical.cancel", id));
}

function viewDetails(id: number) {
  router.get(route("medical.approval.show", id));
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Medical Re-imbursement", href: "" },
  { title: "Approval", href: route("medical.approval.index") },
];
</script>

<template>
  <Head title="Medical Approval" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-4 space-y-4">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
          Medical Approval
        </h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm">
          Manage employee medical reimbursements
        </p>
      </div>

      <!-- Tabs -->
      <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
        <button
          v-for="tab in tabs"
          :key="tab"
          @click="activeTab = tab"
          class="px-4 py-2 text-sm font-medium transition-all duration-200 border-b-2"
          :class="
            activeTab === tab
              ? 'border-blue-600 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
          "
        >
          {{ tab }}
        </button>
      </div>

      <!-- Table Card -->
      <div
        class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
      >
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left">Request #</th>
              <th class="px-4 py-3 text-left">Employee</th>
              <th class="px-4 py-3 text-left">Receipt #</th>
              <th class="px-4 py-3 text-left">Amount</th>
              <th class="px-4 py-3 text-left">Transaction Date</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="item in activeTab === 'PENDING'
                ? props.pending
                : activeTab === 'APPROVED'
                ? props.approved
                : props.rejected"
              :key="item.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-800 transition"
            >
              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                {{ item.reqid }}
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-800 dark:text-gray-100">
                  {{ item.empname }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ item.empnum }}
                </div>
              </td>

              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                {{ item.receiptNumber }}
              </td>

              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                ₱ {{ Number(item.amount).toLocaleString() }}
              </td>

              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                {{ item.transactionDate }}
              </td>

              <td class="px-4 py-3">
                <span
                  class="px-3 py-1 text-xs font-semibold rounded-full"
                  :class="statusBadge(item.status)"
                >
                  {{ item.status }}
                </span>
              </td>

              <td class="px-4 py-3 text-right space-x-2">
                <!-- Pending Actions -->
                <template v-if="activeTab === 'PENDING'">
                  <button
                    @click="viewDetails(item.id)"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition"
                  >
                    View
                  </button>
                </template>

                <!-- Cancel Button -->
                <template v-else>
                  <button
                    @click="cancel(item.id)"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white transition"
                  >
                    Cancel
                  </button>
                </template>
              </td>
            </tr>

            <!-- Empty State -->
            <tr
              v-if="
                (activeTab === 'PENDING'
                  ? props.pending
                  : activeTab === 'APPROVED'
                  ? props.approved
                  : props.rejected
                ).length === 0
              "
            >
              <td
                colspan="6"
                class="px-4 py-10 text-center text-gray-400 dark:text-gray-500"
              >
                No records found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
