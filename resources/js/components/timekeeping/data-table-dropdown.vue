<script setup lang="ts">
import { MoreHorizontal } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import axios from "axios";
import { ref, onMounted, computed, nextTick, watch } from "vue";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import { columns } from "@/components/timekeeping/columns";

//import { toDatetimeLocal } from '@/lib/utils'
console.log(usePage().props);
/* ------------------- Props ------------------- */
const props = defineProps<{
  timekeeping: {
    id: string;
    dated: string | null;
    payrollDate?: string | null;
    shiftCode: string | null;
    correctedShiftCode: string | null;
    shiftcode_id: number | null;
    corrected_shiftcode_id?: number | null;
    timeIn: string | null;
    timeOut: string | null;
    correctedTimeIn?: string | null;
    correctedTimeOut?: string | null;
    reason?: string | null;
    reason_id?: number | null;
    otherReason?: string | null;
    corrections?: {
      status?: "Pending" | "Approved" | "Rejected" | "Superseded" | "Adjusted";
    } | null;
  };

  adjustment?: {
    shiftcode_id: string;
    time_in: string;
    time_out: string;
    adjusted_hours: string;
    reason_id: string;
    other_reason?: string;
    created_at: string;
  } | null;

  currentPayrollDate: string | null;
}>();

/* ------------------- Reactive States ------------------- */
const dropdownOpen = ref(false);
const shiftCodes = ref<ShiftCode[]>([]);
const reasons = ref<Reason[]>([]);
const showCorrectionModal = ref(false);
const formReady = ref(false);
const overtimeList = ref<any[]>([]);
const editingId = ref<number | null>(null);
const showOvertimeModal = ref(false);
const showCorrectionAdjustmentModal = ref(false);
const showViewAdjustmentModal = ref(false);

/* ------------------- Overtime ------------------- */
type OvertimeFormType = {
  timekeeping_id: number;
  startTime: string;
  endTime: string;
  hours: number | null;
  reasons: string | null;
};

// Explicitly provide the generic <OvertimeForm>
const overtimeForm = useForm<OvertimeFormType>({
  timekeeping_id: Number(props.timekeeping.id),
  startTime: "",
  endTime: "",
  hours: null,
  reasons: null,
});

const correctionAdjustment = ref<CorrectionForm>({
  timekeeping_id: Number(props.timekeeping.id),
  shiftCode: null,
  reason: null,
  timeIn: "",
  timeOut: "",
  otherReason: "",
});

type PayrollCutOff = {
  id: number;
  cutOffStart: string;
  cutOffEnd: string;
  payrollDate: string;
  current: string;
};

watch(
  () => [overtimeForm.startTime, overtimeForm.endTime],
  ([start, end]) => {
    if (!start || !end) return;
    const diff = (new Date(end).getTime() - new Date(start).getTime()) / 36e5;
    overtimeForm.hours = diff > 0 ? Number(diff.toFixed(2)) : null;
  }
);

async function handleOpenOvertime() {
  dropdownOpen.value = false; // 🔓 release Radix pointer lock
  await nextTick(); // ⏳ wait for cleanup
  openOvertimeModal(); // ✅ safe to open modal
}

async function openOvertimeModal() {
  showOvertimeModal.value = true;

  const res = await fetch(route("overtime.by-timekeeping", props.timekeeping.id));
  overtimeList.value = await res.json();

  resetForm();
}

function defaultDateTime(time: string = "00:00") {
  const dateOnly = props.timekeeping.dated
    ? props.timekeeping.dated.split("T")[0] // handle if dated is datetime string
    : new Date().toISOString().split("T")[0];
  return `${dateOnly}T${time}`;
}

function resetForm() {
  editingId.value = null;

  Object.assign(overtimeForm, {
    timekeeping_id: Number(props.timekeeping.id),
    startTime: defaultDateTime("00:00"), // auto-fill start time
    endTime: defaultDateTime("00:00"), // auto-fill end time
    hours: null,
  });

  // Clear any previous validation errors
  overtimeForm.errors = {};
}

function submitOvertime() {
  if (editingId.value) {
    overtimeForm.put(route("overtime.update", editingId.value), {
      preserveScroll: true,
      onSuccess: openOvertimeModal,
    });
  } else {
    overtimeForm.post(route("overtime.store"), {
      preserveScroll: true,
      onSuccess: openOvertimeModal,
    });
  }
}

function editOT(ot: any) {
  editingId.value = ot.id;
  overtimeForm.startTime = ot.startTime.slice(0, 16);
  overtimeForm.endTime = ot.endTime.slice(0, 16);
}

function deleteOT(id: number) {
  if (!confirm("Delete this overtime?")) return;

  router.delete(route("overtime.destroy", id), {
    preserveScroll: true,
    onSuccess: () => {
      openOvertimeModal(); // refresh list
    },
    onError: (errors) => {
      alert(errors.overtime ?? "Failed to delete overtime");
    },
  });
}

/* ------------------- Corrections ------------------- */
type ShiftCode = {
  id: number;
  shiftCode: string;
};

type Reason = {
  id: number;
  reasonType: string;
  reasonName: string;
};

type CorrectionForm = {
  timekeeping_id: number;
  shiftCode: number | null;
  reason: number | null;
  timeIn: string;
  timeOut: string;
  otherReason: string;
};

const correction = ref<CorrectionForm>({
  timekeeping_id: Number(props.timekeeping.id),
  shiftCode: null,
  reason: null,
  timeIn: "",
  timeOut: "",
  otherReason: "",
});

/* ------------------- Computed ------------------- */
const showOtherReason = computed(() => {
  return (
    reasons.value.find((r) => r.id === correction.value.reason)?.reasonName === "Others"
  );
});

const adjustment = computed(() => props.adjustment ?? null);

onMounted(async () => {
  const res = await axios.get(route("timekeeping.correction.form-data"));

  shiftCodes.value = res.data.shiftCodes.map((s: any) => ({
    ...s,
    id: Number(s.id),
  }));

  reasons.value = res.data.reasons.map((r: any) => ({
    ...r,
    id: Number(r.id),
  }));

  formReady.value = true;
});

function toDatetimeLocalString(value?: string | null): string {
  if (!value) return "";

  // "2025-01-22 06:55:00" → "2025-01-22T06:55"
  if (value.includes(" ")) {
    const [date, time] = value.split(" ");
    return `${date}T${time.slice(0, 5)}`;
  }

  // "2025-01-22T06:55:00" → "2025-01-22T06:55"
  return value.slice(0, 16);
}

function resolveShiftCodeId(): number | null {
  const code =
    props.timekeeping.correctedShiftCode ?? props.timekeeping.shiftCode ?? null;

  if (!code) return null;

  const match = shiftCodes.value.find((s) => s.shiftCode === code);

  return match?.id ?? null;
}

function resolvedTimeIn(): string {
  return toDatetimeLocalString(
    props.timekeeping.correctedTimeIn ??
      props.timekeeping.timeIn ??
      props.timekeeping.dated
  );
}

function resolvedTimeOut(): string {
  return toDatetimeLocalString(
    props.timekeeping.correctedTimeOut ??
      props.timekeeping.timeOut ??
      props.timekeeping.dated
  );
}

const selectedShift = resolveShiftCodeId();

/* ------------------- Functions ------------------- */
async function openCorrectionModal() {
  if (!formReady.value) return;

  // Resolve reason safely
  let selectedReason: number | null = null;
  if (props.timekeeping.reason) {
    const match = reasons.value.find((r) => r.reasonName === props.timekeeping.reason);
    if (match) selectedReason = match.id;
  }

  correction.value = {
    timekeeping_id: Number(props.timekeeping.id),

    // ✅ auto-select corrected → original
    shiftCode: selectedShift,

    reason: selectedReason,

    // ✅ NO timezone conversion
    timeIn: resolvedTimeIn(),
    timeOut: resolvedTimeOut(),

    otherReason: props.timekeeping.otherReason ?? "",
  };

  showCorrectionModal.value = true;
  await nextTick();

  correction.value.shiftCode = resolveShiftCodeId();
}

function closeCorrectionModal() {
  showCorrectionModal.value = false;
}

async function submitCorrection() {
  try {
    await axios.post(route("timekeeping.correction.store"), correction.value);

    closeCorrectionModal();

    window.location.reload();
  } catch (error: any) {
    console.error(error.response?.data?.errors);
    alert(JSON.stringify(error.response?.data?.errors, null, 2));
  }
}

// Computed to get human-readable reason name for a timekeeping record
const timekeepingReasonName = computed(() => {
  if (!props.timekeeping.reason_id) return "—";

  // Ensure the types match
  const reasonId = Number(props.timekeeping.reason_id);
  const reason = reasons.value.find((r) => r.id === reasonId);

  return reason?.reasonName ?? "—";
});

// Computed property to get the latest correction status
const timekeepingStatus = computed(() => {
  // If there are corrections, take the latest one
  const correction = props.timekeeping.corrections;

  return correction?.status ?? "—"; // fallback if no status
});
// or import { Inertia } from '@inertiajs/inertia'

const deleteRecord = async (id: string) => {
  if (!confirm("Are you sure you want to delete this correction?")) return;

  try {
    const res = await axios.patch(
      route("timekeeping.correction.delete", {
        timekeeping: id,
        cutoff_date: props.currentPayrollDate,
      })
    );

    if (res.data.success) {
      showCorrectionModal.value = false;
      showCorrectionAdjustmentModal.value = false;
      showViewAdjustmentModal.value = false;

      // Reload page or update local state
      window.location.reload();
    } else {
      alert(res.data.message ?? "Failed to delete correction");
    }
  } catch (error: any) {
    console.error(error.response?.data ?? error);
    alert(error.response?.data?.message ?? "Failed to delete correction");
  }
};

function formatISOToCustom(dateTime: string | null | undefined) {
  if (!dateTime) return "—";
  // Remove the fractional seconds and Z
  const clean = dateTime.split(".")[0].replace("T", " ");
  const [date, time] = clean.split(" ");
  if (!date || !time) return dateTime;
  const [year, month, day] = date.split("-");
  return `${month}-${day}-${year} ${time}`;
}

async function openCorrectionAdjustmentModal() {
  correctionAdjustment.value = {
    timekeeping_id: Number(props.timekeeping.id),
    shiftCode: resolveShiftCodeId(),
    reason: null,
    timeIn: resolvedTimeIn(),
    timeOut: resolvedTimeOut(),
    otherReason: "",
  };

  showCorrectionAdjustmentModal.value = true;
  await nextTick();
}

function closeCorrectionAdjustmentModal() {
  showCorrectionAdjustmentModal.value = false;
}

async function submitCorrectionAdjustment() {
  try {
    await axios.post(route("timekeeping.correction.store"), correctionAdjustment.value);
    closeCorrectionAdjustmentModal();
    window.location.reload();
  } catch (error: any) {
    console.error(error.response?.data?.errors);
    alert(JSON.stringify(error.response?.data?.errors, null, 2));
  }
}

// Computed to get human-readable values for the modal
const adjustmentShiftCode = computed(() => {
  if (!props.adjustment) return "—";

  const shiftId = Number(props.adjustment.shiftcode_id); // convert string → number
  const shift = shiftCodes.value.find((s) => s.id === shiftId);

  return shift?.shiftCode ?? "—";
});

const adjustmentReasonName = computed(() => {
  if (!props.adjustment) return "—";

  const reasonId = Number(props.adjustment.reason_id); // convert string → number
  const reason = reasons.value.find((r) => r.id === reasonId);

  return reason?.reasonName ?? "—";
});

function openViewAdjustmentModal() {
  if (!adjustment.value) return;
  showViewAdjustmentModal.value = true;
}

function closeViewAdjustmentModal() {
  showViewAdjustmentModal.value = false;
}

const hasAdjustment = computed(() => !!props.adjustment);

const isApproved = computed(() => props.timekeeping.corrections?.status === "Approved");
const isPending = computed(() => props.timekeeping.corrections?.status === "Pending");

// CURRENT cutoff
const canFileCorrection = computed(() => isCurrentCutoff.value && !isApproved.value);
const canFileOvertime = computed(() => isCurrentCutoff.value);
const canDelete = computed(() => isCurrentCutoff.value && isPending.value);

// PREVIOUS cutoff

const canCorrectionAdjust = computed(
  () => !isCurrentCutoff.value && !hasAdjustment.value
);
const canOvertimeAdjust = computed(() => !isCurrentCutoff.value);
const canViewAdjustment = computed(() => !isCurrentCutoff.value && hasAdjustment.value);
const canDeleteAdjustment = computed(() => !isCurrentCutoff.value && isPending.value);

const isCurrentCutoff = computed(() => {
  if (!props.currentPayrollDate) return false;
  if (!props.timekeeping.payrollDate) return false;

  // Compare only the date part (YYYY-MM-DD)
  const current = new Date(props.currentPayrollDate).toISOString().slice(0, 10);
  const rowDate = new Date(props.timekeeping.payrollDate).toISOString().slice(0, 10);

  return current === rowDate;
});

console.log(
  "rowPayrollDate:",
  props.timekeeping.payrollDate,
  "currentPayrollDate:",
  props.currentPayrollDate
);
</script>

<template>
  <DropdownMenu v-model:open="dropdownOpen">
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" class="w-8 h-8 p-0">
        <MoreHorizontal class="w-4 h-4" />
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end">
      <DropdownMenuLabel
        class="text-xs font-semibold uppercase text-gray-500 pointer-events-none"
      >
        Actions
      </DropdownMenuLabel>

      <DropdownMenuSeparator />

      <!-- CURRENT cutoff -->
      <template v-if="isCurrentCutoff">
        <DropdownMenuItem v-if="canFileCorrection" @click="openCorrectionModal">
          {{ isPending ? "Edit Correction" : "File Correction" }}
        </DropdownMenuItem>

        <DropdownMenuItem v-if="canFileOvertime" @click="handleOpenOvertime">
          File Overtime
        </DropdownMenuItem>

        <DropdownMenuItem
          v-if="canDelete"
          class="text-red-600"
          @click="deleteRecord(props.timekeeping.id)"
        >
          Delete Correction
        </DropdownMenuItem>
      </template>

      <!-- PREVIOUS cutoff -->
      <template v-else>
        <DropdownMenuItem
          v-if="canCorrectionAdjust"
          @click="openCorrectionAdjustmentModal"
        >
          Correction Adjustment
        </DropdownMenuItem>

        <DropdownMenuItem v-if="canOvertimeAdjust" @click="handleOpenOvertime">
          Overtime Adjustment
        </DropdownMenuItem>

        <DropdownMenuItem v-if="canViewAdjustment" @click="openViewAdjustmentModal">
          View Adjustment
        </DropdownMenuItem>
        <DropdownMenuItem
          v-if="canDeleteAdjustment"
          class="text-red-600"
          @click="deleteRecord(props.timekeeping.id)"
        >
          Delete Correction
        </DropdownMenuItem>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>

  <!-- ⭐ Correction Modal -->
  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0 backdrop-blur-0"
      enter-to-class="opacity-100 backdrop-blur-sm"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100 backdrop-blur-sm"
      leave-to-class="opacity-0 backdrop-blur-0"
    >
      <div
        v-if="showCorrectionModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      >
        <Transition
          enter-active-class="duration-300 ease-out"
          enter-from-class="opacity-0 scale-90 translate-y-6"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-90 translate-y-6"
        >
          <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 relative transform transition-all"
          >
            <button
              class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 transition"
              @click="closeCorrectionModal"
            >
              ✕
            </button>

            <h2 class="text-xl font-bold mb-4">File Timekeeping Correction</h2>

            <form @submit.prevent="submitCorrection" class="space-y-4">
              <!-- Shift Code -->
              <label class="font-medium block mb-1 dark:text-gray-200">Shift Code</label>
              <select
                v-model.number="correction.shiftCode"
                class="w-full border rounded-lg p-2 bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
                required
              >
                <option :value="null" disabled>Select shift</option>
                <option v-for="shift in shiftCodes" :key="shift.id" :value="shift.id">
                  {{ shift.shiftCode }}
                </option>
              </select>

              <!-- Time In -->
              <div>
                <label class="font-medium block mb-1 dark:text-gray-200">Time In</label>
                <input
                  type="datetime-local"
                  v-model="correction.timeIn"
                  class="w-full border rounded-lg p-2 bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
                  required
                />
              </div>

              <!-- Time Out -->
              <div>
                <label class="font-medium block mb-1 dark:text-gray-200">Time Out</label>
                <input
                  type="datetime-local"
                  v-model="correction.timeOut"
                  class="w-full border rounded-lg p-2 bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
                  required
                />
              </div>

              <!-- Reason -->
              <label class="font-medium block mb-1 dark:text-gray-200">Reason</label>
              <select
                v-model.number="correction.reason"
                required
                class="w-full border rounded-lg p-2 bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
              >
                <option :value="null" disabled>Select reason</option>
                <option v-for="reason in reasons" :key="reason.id" :value="reason.id">
                  {{ reason.reasonName }}
                </option>
              </select>

              <!-- Other Reason -->
              <div v-if="showOtherReason" class="mt-2">
                <label class="font-medium block mb-1 dark:text-gray-200"
                  >Other Reason</label
                >
                <textarea
                  v-model="correction.otherReason"
                  :required="showOtherReason"
                  class="w-full border rounded-lg p-2 bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
                  rows="4"
                  placeholder="Please specify the reason"
                ></textarea>
              </div>

              <button
                type="submit"
                class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg"
              >
                Submit Correction
              </button>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>

  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0 backdrop-blur-0"
      enter-to-class="opacity-100 backdrop-blur-sm"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100 backdrop-blur-sm"
      leave-to-class="opacity-0 backdrop-blur-0"
    >
      <div
        v-if="showCorrectionAdjustmentModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      >
        <Transition
          enter-active-class="duration-300 ease-out"
          enter-from-class="opacity-0 scale-90 translate-y-6"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-90 translate-y-6"
        >
          <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 relative transform transition-all"
          >
            <button
              class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 transition"
              @click="closeCorrectionAdjustmentModal"
            >
              ✕
            </button>

            <h2 class="text-xl font-bold mb-4">Correction Adjustment</h2>

            <form @submit.prevent="submitCorrectionAdjustment" class="space-y-4">
              <!-- Shift Code -->
              <label class="font-medium block mb-1 dark:text-gray-200">Shift Code</label>
              <select
                v-model.number="correctionAdjustment.shiftCode"
                required
                class="w-full border rounded-lg p-2 bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
              >
                <option :value="null" disabled>Select shift</option>
                <option v-for="shift in shiftCodes" :key="shift.id" :value="shift.id">
                  {{ shift.shiftCode }}
                </option>
              </select>

              <!-- Time In -->
              <div>
                <label class="font-medium block mb-1 dark:text-gray-200">Time In</label>
                <input
                  type="datetime-local"
                  v-model="correctionAdjustment.timeIn"
                  required
                  class="w-full border rounded-lg p-2"
                />
              </div>

              <!-- Time Out -->
              <div>
                <label class="font-medium block mb-1 dark:text-gray-200">Time Out</label>
                <input
                  type="datetime-local"
                  v-model="correctionAdjustment.timeOut"
                  required
                  class="w-full border rounded-lg p-2"
                />
              </div>

              <!-- Reason -->
              <label class="font-medium block mb-1 dark:text-gray-200">Reason</label>
              <select
                v-model.number="correctionAdjustment.reason"
                required
                class="w-full border rounded-lg p-2 bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
              >
                <option :value="null" disabled>Select reason</option>
                <option v-for="reason in reasons" :key="reason.id" :value="reason.id">
                  {{ reason.reasonName }}
                </option>
              </select>

              <!-- Other Reason -->
              <div
                v-if="
                  reasons.find((r) => r.id === correctionAdjustment.reason)
                    ?.reasonName === 'Others'
                "
                class="mt-2"
              >
                <label class="font-medium block mb-1 dark:text-gray-200"
                  >Other Reason</label
                >
                <textarea
                  v-model="correctionAdjustment.otherReason"
                  required
                  class="w-full border rounded-lg p-2"
                  rows="4"
                ></textarea>
              </div>

              <button
                type="submit"
                class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-lg"
              >
                Submit Adjustment
              </button>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>

  <!-- ⭐ Overtime Modal -->
  <teleport to="body">
    <div
      v-if="showOvertimeModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center"
    >
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">File Overtime</h2>

        <ul
          v-if="Object.keys(overtimeForm.errors).length"
          class="mb-4 p-2 bg-red-100 text-red-700 rounded"
        >
          <li v-for="(err, key) in overtimeForm.errors" :key="key">{{ err }}</li>
        </ul>

        <table class="w-full text-sm mb-4">
          <thead>
            <tr>
              <th>Start</th>
              <th>End</th>
              <th>Hours</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ot in overtimeList" :key="ot.id">
              <td>{{ formatISOToCustom(ot.startTime) }}</td>
              <td>{{ formatISOToCustom(ot.endTime) }}</td>
              <td>{{ ot.hours }}</td>
              <td>
                <button @click="editOT(ot)">✏️</button>
                <button @click="deleteOT(ot.id)">🗑</button>
              </td>
            </tr>
          </tbody>
        </table>

        <form @submit.prevent="submitOvertime" class="space-y-4">
          <div>
            <Label>Start Time</Label>
            <input
              type="datetime-local"
              v-model="overtimeForm.startTime"
              required
              class="w-full p-2 border rounded"
            />
            <div class="text-sm text-red-600" v-if="overtimeForm.errors.startTime">
              {{ overtimeForm.errors.startTime }}
            </div>
          </div>

          <div>
            <Label>End Time</Label>
            <input
              type="datetime-local"
              v-model="overtimeForm.endTime"
              required
              class="w-full p-2 border rounded"
            />
            <div class="text-sm text-red-600" v-if="overtimeForm.errors.endTime">
              {{ overtimeForm.errors.endTime }}
            </div>
          </div>

          <div>
            <Label>Hours</Label>
            <input
              type="number"
              v-model="overtimeForm.hours"
              readonly
              class="w-full p-2 border rounded"
            />
          </div>

          <div>
            <Label for="Reasons">Reasons</Label>
            <textarea
              id="Employee notes"
              placeholder="Overtime reasons"
              v-model="overtimeForm.reasons"
              rows="3"
              class="w-full p-2 border rounded"
            ></textarea>
            <div class="text-sm text-red-600" v-if="overtimeForm.errors.reasons">
              {{ overtimeForm.errors.reasons }}
            </div>
          </div>

          <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded transition-all duration-200 ease-in-out hover:bg-blue-700 hover:shadow-lg active:scale-95 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
          >
            {{ editingId ? "Update" : "Add" }} Overtime
          </button>
        </form>
        <button
          @click="showOvertimeModal = false"
          class="mt-4 w-full bg-blue-400 text-white py-2 rounded transition-all duration-200 ease-in-out hover:bg-blue-500 hover:shadow-lg active:scale-95 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:ring-offset-2"
        >
          Close
        </button>
      </div>
    </div>
  </teleport>

  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0 backdrop-blur-0"
      enter-to-class="opacity-100 backdrop-blur-sm"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100 backdrop-blur-sm"
      leave-to-class="opacity-0 backdrop-blur-0"
    >
      <div
        v-if="showViewAdjustmentModal && adjustment"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      >
        <Transition
          enter-active-class="duration-300 ease-out"
          enter-from-class="opacity-0 scale-90 translate-y-6"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-90 translate-y-6"
        >
          <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 relative"
          >
            <button
              class="absolute top-3 right-3 text-gray-500 hover:text-gray-700"
              @click="closeViewAdjustmentModal"
            >
              ✕
            </button>

            <h2 class="text-xl font-bold mb-4">Correction Adjustment Details</h2>

            <div class="space-y-4 text-sm">
              <!-- Shift Code Comparison -->
              <div class="flex justify-between">
                <span class="font-semibold">Shift Code:</span>
                <div class="flex space-x-2">
                  <span class="line-through text-gray-400">{{
                    correction.shiftCode ?? timekeeping.shiftCode
                  }}</span>
                  <span class="text-green-600 font-medium">{{
                    adjustmentShiftCode
                  }}</span>
                </div>
              </div>

              <!-- Time In Comparison -->
              <div class="flex justify-between">
                <span class="font-semibold">Time In:</span>
                <div class="flex space-x-2">
                  <span class="line-through text-gray-400">{{
                    formatISOToCustom(correction.timeIn ?? timekeeping.timeIn)
                  }}</span>
                  <span class="text-green-600 font-medium">{{
                    formatISOToCustom(adjustment.time_in)
                  }}</span>
                </div>
              </div>

              <!-- Time Out Comparison -->
              <div class="flex justify-between">
                <span class="font-semibold">Time Out:</span>
                <div class="flex space-x-2">
                  <span class="line-through text-gray-400">{{
                    formatISOToCustom(correction.timeOut ?? timekeeping.timeOut)
                  }}</span>
                  <span class="text-green-600 font-medium">{{
                    formatISOToCustom(adjustment.time_out)
                  }}</span>
                </div>
              </div>

              <!-- Reason Comparison -->
              <div class="flex justify-between">
                <span class="font-semibold">Reason:</span>
                <div class="flex space-x-2">
                  <span class="text-green-600 font-medium">{{
                    adjustmentReasonName
                  }}</span>
                </div>
              </div>

              <!-- Other Reason (if exists) -->
              <div v-if="timekeeping.reason_id">
                <span class="font-semibold">Other Reason:</span>
                <div class="flex space-x-2">
                  <span class="text-green-600 font-medium">{{
                    timekeeping.otherReason || "-"
                  }}</span>
                </div>
              </div>

              <div class="text-xs text-gray-500 pt-2">
                Filed on {{ formatISOToCustom(adjustment.created_at) }}
              </div>
            </div>

            <div class="flex justify-between">
              <span class="font-semibold">Adjusted Hours:</span>
              <div class="flex space-x-2">
                <span class="text-green-600 font-medium">{{
                  adjustment.adjusted_hours
                }}</span>
              </div>
            </div>

            <div class="flex justify-between">
              <span class="font-semibold">Status:</span>
              <div class="flex space-x-2">
                <span class="text-green-600 font-medium">{{ timekeepingStatus }}</span>
              </div>
            </div>

            <button
              class="mt-6 w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700"
              @click="closeViewAdjustmentModal"
            >
              Close
            </button>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
