<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref, computed } from "vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  pendingCorrections?: any[];
  approvedCorrections?: any[];
  rejectedCorrections?: any[];
  adjustments?: any[];
}>();

const pendingCorrections = computed(() => props.pendingCorrections || []);
const approvedCorrections = computed(() => props.approvedCorrections || []);
const rejectedCorrections = computed(() => props.rejectedCorrections || []);
const adjustments = computed(() => props.adjustments || []);

const activeTab = ref<"pending" | "approved" | "rejected" | "adjustments">("pending");
const selected = ref<any | null>(null);
const rejectReason = ref("");
const selectedIds = ref<number[]>([]);

function toggle(id: number) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter((i) => i !== id);
  } else {
    selectedIds.value.push(id);
  }
}

function toggleAll(e: Event) {
  if (activeTab.value !== "pending") return;

  const checked = (e.target as HTMLInputElement).checked;

  selectedIds.value = checked ? pendingCorrections.value.map((c) => c.id) : [];
}

function bulkApprove() {
  router.post(
    route("approvals.timekeeping.bulk-approve"),
    { ids: selectedIds.value },
    {
      onSuccess: () => {
        selectedIds.value = [];
      },
    }
  );
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Approval", href: "" },
  { title: "Corrections", href: route("approvals.timekeeping") },
];

// PENDING ACTIONS
function approve(id: number) {
  router.post(
    route("approvals.timekeeping.approve", id),
    {},
    { onSuccess: () => close() }
  );
}

function reject(id: number) {
  router.post(
    route("approvals.timekeeping.reject", { correction: id }),
    { reason: rejectReason.value },
    { onSuccess: () => close() }
  );
}

// APPROVED ACTIONS
function cancelApproval(id: number) {
  // Find the row
  const index = approvedCorrections.value.findIndex((c) => c.id === id);
  if (index !== -1) {
    const row = approvedCorrections.value.splice(index, 1)[0];
    pendingCorrections.value.unshift(row); // move it to pending
  }

  close(); // close modal

  router.post(route("approvals.timekeeping.cancel", id));
}

function cancelRejection(id: number) {
  close();

  router.post(
    route("approvals.timekeeping.cancel-rejection", id),
    {},
    {
      preserveScroll: true,
      preserveState: true,
    }
  );
}

function cancelAdjustment(id: number) {
  if (!confirm("Are you sure you want to cancel this adjustment?")) return;
  close();
  router.post(
    route("approvals.timekeeping.cancel-adjustment", id),
    {},
    {
      onSuccess: () => {
        // Remove from adjustments list locally
        const index = adjustments.value.findIndex((a) => a.id === id);
        if (index !== -1) adjustments.value.splice(index, 1);
      },
    }
  );
}

// MODAL CONTROL
function open(c: any) {
  selected.value = c;
}
function close() {
  selected.value = null;
  rejectReason.value = "";
}

function formatISOToCustom(dateTime: string | null | undefined) {
  if (!dateTime) return "—";
  // Remove the fractional seconds and Z
  const clean = dateTime.split(".")[0].replace("T", " ");
  const [date, time] = clean.split(" ");
  if (!date || !time) return dateTime;
  const [year, month, day] = date.split("-");
  return `${month}-${day}-${year} ${time}`;
}

function formatISOToDate(dateTime: string | null | undefined) {
  if (!dateTime) return "—";
  // Remove the fractional seconds and Z
  const clean = dateTime.split(".")[0].replace("T", " ");
  const [date, time] = clean.split(" ");
  if (!date || !time) return dateTime;
  const [year, month, day] = date.split("-");
  return `${month}-${day}-${year}`;
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <h1 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">
        Timekeeping Corrections
      </h1>

      <!-- TABS -->
      <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
        <button
          class="px-4 py-2 font-medium"
          :class="
            activeTab === 'pending'
              ? 'border-b-2 border-blue-600 text-blue-600'
              : 'text-gray-500 dark:text-gray-400'
          "
          @click="activeTab = 'pending'"
        >
          Pending
        </button>
        <button
          class="px-4 py-2 font-medium"
          :class="
            activeTab === 'approved'
              ? 'border-b-2 border-yellow-600 text-yellow-600'
              : 'text-gray-500 dark:text-gray-400'
          "
          @click="activeTab = 'approved'"
        >
          Approved
        </button>
        <button
          class="px-4 py-2 font-medium"
          :class="
            activeTab === 'rejected'
              ? 'border-b-2 border-yellow-600 text-yellow-600'
              : 'text-gray-500 dark:text-gray-400'
          "
          @click="activeTab = 'rejected'"
        >
          Rejected
        </button>
        <button
          class="px-4 py-2 font-medium"
          :class="
            activeTab === 'adjustments'
              ? 'border-b-2 border-purple-600 text-purple-600'
              : 'text-gray-500 dark:text-gray-400'
          "
          @click="activeTab = 'adjustments'"
        >
          Adjustments
        </button>
      </div>

      <!-- PENDING TAB -->
      <div
        v-if="activeTab === 'pending'"
        class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto"
      >
        <div class="flex justify-end mb-3">
          <button
            class="px-4 py-2 rounded-lg text-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
            :disabled="!selectedIds.length"
            @click="bulkApprove"
          >
            Approve Selected ({{ selectedIds.length }})
          </button>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="p-3 text-center">
                <input
                  type="checkbox"
                  :checked="
                    pendingCorrections.length > 0 &&
                    selectedIds.length === pendingCorrections.length
                  "
                  @change="toggleAll"
                />
              </th>
              <th class="p-3 text-left">Employee</th>
              <th class="p-3 text-left">Date</th>
              <th class="p-3 text-left">Shift</th>
              <th class="p-3 text-left">Time In</th>
              <th class="p-3 text-left">Time Out</th>
              <th class="p-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in pendingCorrections"
              :key="c.id"
              class="border-t dark:border-gray-700"
            >
              <td class="p-3 text-center">
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(c.id)"
                  @change="toggle(c.id)"
                />
              </td>
              <td class="p-3">{{ c.creator?.name || "—" }}</td>
              <td class="p-3">{{ formatISOToDate(c.timekeeping?.dated) || "—" }}</td>
              <td class="p-3">{{ c.shiftcode?.shiftCode || "—" }}</td>
              <td class="p-3">{{ formatISOToCustom(c.time_in) || "—" }}</td>
              <td class="p-3">{{ formatISOToCustom(c.time_out) || "—" }}</td>
              <td class="p-3 text-center">
                <button class="text-blue-600 dark:text-blue-400" @click="open(c)">
                  Review
                </button>
              </td>
            </tr>
            <tr v-if="!pendingCorrections.length">
              <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">
                No pending corrections 🎉
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- APPROVED TAB -->
      <div
        v-if="activeTab === 'approved'"
        class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto"
      >
        <table class="w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="p-3 text-left">Employee</th>
              <th class="p-3 text-left">Date</th>
              <th class="p-3 text-left">Shift</th>
              <th class="p-3 text-left">Time In</th>
              <th class="p-3 text-left">Time Out</th>
              <th class="p-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in approvedCorrections"
              :key="c.id"
              class="border-t dark:border-gray-700"
            >
              <td class="p-3">{{ c.creator?.name || "—" }}</td>
              <td class="p-3">{{ formatISOToDate(c.timekeeping?.dated) || "—" }}</td>
              <td class="p-3">{{ c.shiftcode?.shiftCode || "—" }}</td>
              <td class="p-3">{{ formatISOToCustom(c.time_in) || "—" }}</td>
              <td class="p-3">{{ formatISOToCustom(c.time_out) || "—" }}</td>
              <td class="p-3 text-center">
                <button class="text-yellow-600 dark:text-yellow-400" @click="open(c)">
                  Cancel Approval
                </button>
              </td>
            </tr>
            <tr v-if="!approvedCorrections.length">
              <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">
                No approved corrections 🎉
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- REJECTED TAB -->
      <div
        v-if="activeTab === 'rejected'"
        class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto"
      >
        <table class="w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="p-3 text-left">Employee</th>
              <th class="p-3 text-left">Date</th>
              <th class="p-3 text-left">Shift</th>
              <th class="p-3 text-left">Time In</th>
              <th class="p-3 text-left">Time Out</th>
              <th class="p-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in rejectedCorrections"
              :key="c.id"
              class="border-t dark:border-gray-700"
            >
              <td class="p-3">{{ c.creator?.name || "—" }}</td>
              <td class="p-3">{{ formatISOToDate(c.timekeeping?.dated) || "—" }}</td>
              <td class="p-3">{{ c.shiftcode?.shiftCode || "—" }}</td>
              <td class="p-3">{{ formatISOToCustom(c.time_in) || "—" }}</td>
              <td class="p-3">{{ formatISOToCustom(c.time_out) || "—" }}</td>
              <td class="p-3 text-center">
                <button class="text-yellow-600 dark:text-yellow-400" @click="open(c)">
                  Cancel Rejection
                </button>
              </td>
            </tr>
            <tr v-if="!rejectedCorrections.length">
              <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">
                No rejected corrections 🎉
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ADJUSTMENT TAB -->
      <div
        v-if="activeTab === 'adjustments'"
        class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto"
      >
        <table class="w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="p-3 text-left">Employee</th>
              <th class="p-3 text-left">Date</th>
              <th class="p-3 text-left">Shift</th>
              <th class="p-3 text-left">Time In</th>
              <th class="p-3 text-left">Time Out</th>
              <th class="p-3 text-left">Reg Hours</th>
              <th class="p-3 text-left">OT</th>
              <th class="p-3 text-left">Late</th>
              <th class="p-3 text-left">Undertime</th>
              <th class="p-3 text-left">NSD</th>
              <th class="p-3 text-left">Adjusted Hours</th>
              <th class="p-3 text-left">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="a in adjustments"
              :key="a.id"
              class="border-t dark:border-gray-700"
            >
              <td class="p-3">{{ a.empnum }}</td>
              <td class="p-3">{{ formatISOToDate(a.dated) }}</td>
              <td class="p-3">{{ a.shiftcode?.shiftCode || "—" }}</td>
              <td class="p-3">{{ formatISOToCustom(a.time_in) }}</td>
              <td class="p-3">{{ formatISOToCustom(a.time_out) }}</td>
              <td class="p-3">{{ a.reg_hours }}</td>
              <td class="p-3">{{ a.overtime }}</td>
              <td class="p-3">{{ a.late }}</td>
              <td class="p-3">{{ a.undertime }}</td>
              <td class="p-3">{{ a.nsd }}</td>
              <td class="p-3">{{ a.adjusted_hours }}</td>
              <td class="p-3 text-center">
                <button class="text-purple-600 dark:text-purple-400" @click="open(a)">
                  Cancel
                </button>
              </td>
            </tr>
            <tr v-if="!adjustments.length">
              <td colspan="12" class="p-6 text-center text-gray-500 dark:text-gray-400">
                No adjustments 🎉
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- MODAL -->
      <div
        v-if="selected"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
      >
        <div
          class="w-full max-w-2xl rounded-xl shadow-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"
        >
          <div
            class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700"
          >
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
              {{
                activeTab === "pending"
                  ? "Review Timekeeping Correction"
                  : activeTab === "approved"
                  ? "Cancel Approved Correction"
                  : "Cancel Rejected Correction"
              }}
            </h2>
            <button
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              @click="close"
            >
              ✕
            </button>
          </div>

          <div class="px-6 py-4 space-y-4">
            <div v-if="activeTab === 'pending'">
              <!-- comparison table + rejection reason same as before -->
              <table class="w-full text-sm mb-4">
                <thead
                  class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300"
                >
                  <tr>
                    <th class="px-3 py-2 text-left">Field</th>
                    <th class="px-3 py-2 text-left">Original</th>
                    <th class="px-3 py-2 text-left">Requested</th>
                  </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                  <tr>
                    <td class="px-3 py-2 font-medium">Shift</td>
                    <td class="px-3 py-2">{{ selected.timekeeping.shiftCode }}</td>
                    <td class="px-3 py-2 text-blue-600 dark:text-blue-400 font-medium">
                      {{ selected.shiftcode.shiftCode }}
                    </td>
                  </tr>
                  <tr>
                    <td class="px-3 py-2 font-medium">Time In</td>
                    <td class="px-3 py-2">
                      {{ formatISOToCustom(selected.timekeeping.timeIn) }}
                    </td>
                    <td class="px-3 py-2 text-blue-600 dark:text-blue-400 font-medium">
                      {{ formatISOToCustom(selected.time_in) }}
                    </td>
                  </tr>
                  <tr>
                    <td class="px-3 py-2 font-medium">Time Out</td>
                    <td class="px-3 py-2">
                      {{ formatISOToCustom(selected.timekeeping.timeOut) }}
                    </td>
                    <td class="px-3 py-2 text-blue-600 dark:text-blue-400 font-medium">
                      {{ formatISOToCustom(selected.time_out) }}
                    </td>
                  </tr>
                </tbody>
              </table>
              <div class="text-sm text-gray-700 dark:text-gray-300">
                <span class="font-semibold">Reason:</span>
                {{ selected.reason.reasonName }}
              </div>
              <div>
                <label
                  class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Rejection Reason (optional)</label
                >
                <textarea
                  v-model="rejectReason"
                  rows="3"
                  class="w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:outline-none"
                  placeholder="Provide a reason if rejecting this correction"
                />
              </div>
            </div>

            <div v-else-if="activeTab === 'adjustments'">
              <div class="text-sm text-gray-700 dark:text-gray-300">
                Are you sure you want to cancel the adjustment for
                <strong>{{ selected.empnum }}</strong> on
                <strong>{{ formatISOToDate(selected.dated) }}</strong
                >?
              </div>
            </div>

            <div v-else>
              <div class="text-sm text-gray-700 dark:text-gray-300">
                Are you sure you want to cancel the approval for
                <strong>{{ selected.creator?.name || "—" }}</strong> on
                <strong>{{ formatISOToDate(selected.timekeeping?.dated) || "—" }}</strong
                >?
              </div>
            </div>
          </div>

          <div
            class="flex justify-end gap-2 px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
          >
            <button
              v-if="activeTab === 'pending'"
              class="px-4 py-2 rounded-lg text-sm text-white bg-red-600 hover:bg-red-700"
              @click="reject(selected.id)"
            >
              Reject
            </button>
            <button
              v-if="activeTab === 'pending'"
              class="px-4 py-2 rounded-lg text-sm text-white bg-green-600 hover:bg-green-700"
              @click="approve(selected.id)"
            >
              Approve
            </button>
            <button
              v-if="activeTab === 'approved'"
              class="px-4 py-2 rounded-lg text-sm text-white bg-yellow-600 hover:bg-yellow-700"
              @click="cancelApproval(selected.id)"
            >
              Yes, Cancel Approval
            </button>
            <button
              v-if="activeTab === 'rejected'"
              class="px-4 py-2 rounded-lg text-sm text-white bg-yellow-600 hover:bg-yellow-700"
              @click="cancelRejection(selected.id)"
            >
              Yes, Cancel Rejection
            </button>
            <button
              v-if="activeTab === 'adjustments'"
              class="px-4 py-2 rounded-lg text-sm text-white bg-purple-600 hover:bg-purple-700"
              @click="cancelAdjustment(selected.id)"
            >
              Yes, Cancel Adjustment
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
