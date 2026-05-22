<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import {
  startOfMonth,
  addMonths,
  eachDayOfInterval,
  endOfMonth,
  format,
  isWeekend,
  isSameDay,
  addDays,
  isWithinInterval,
  parseISO,
} from "date-fns";
import type { BreadcrumbItem } from "@/types";
import { route } from "ziggy-js";
import { router } from "@inertiajs/vue3";

interface LeaveType {
  id: number;
  name: string;
}
interface Balance {
  leave_type_id: number;
  balance: number;
}
interface UserLeave {
  id: number;
  start_date: string;
  end_date: string;
  status: "approved" | "pending";
  leave_type_name?: string;
  days?: number;
  hours?: number;
  reason?: string;
}

const props = defineProps<{
  leaveTypes: LeaveType[];
  balances: Balance[];
  holidays: string[];
  userLeaves: UserLeave[];
}>();

// Form
const form = useForm({
  leave_type_id: "" as string | number,
  start_date: "",
  end_date: "",
  days: 0,
  hours: 0,
  reason: "",
  attachment: null as File | null,
  half_day: false,
});

// State
//const holidays = ['2025-12-25', '2025-12-31']
const isHoursManuallyEdited = ref(false);
const selectedDates = ref<Date[]>([]);
const selectedLeave = ref<UserLeave | null>(null);
const showLeaveModal = ref(false);

// Helpers
const isInLeaveRange = (day: Date, leave: UserLeave) =>
  isWithinInterval(day, {
    start: parseISO(leave.start_date),
    end: parseISO(leave.end_date),
  });

const isApprovedLeave = (day: Date) =>
  props.userLeaves.some((l) => l.status === "approved" && isInLeaveRange(day, l));

const isPendingLeave = (day: Date) =>
  props.userLeaves.some((l) => l.status === "pending" && isInLeaveRange(day, l));

const getBalance = (typeId: string | number) =>
  props.balances.find((b) => b.leave_type_id === Number(typeId))?.balance ?? 0;

const handleFile = (e: Event) => {
  const input = e.target as HTMLInputElement;
  if (input.files?.length) form.attachment = input.files[0];
};

const maxHours = computed(() => getBalance(form.leave_type_id)); // 8 hours per day
const isExceedingBalance = computed(() => form.hours > maxHours.value);

const resetForm = () => {
  form.leave_type_id = "";
  form.start_date = "";
  form.end_date = "";
  form.days = 0;
  form.hours = 0;
  form.reason = "";
  form.attachment = null;
  form.half_day = false;
  isHoursManuallyEdited.value = false;
  selectedDates.value = [];
};

const dateRange = computed(() => {
  if (!selectedDates.value.length) return [];
  const start = selectedDates.value[0];
  const end = selectedDates.value[selectedDates.value.length - 1];
  const range: Date[] = [];
  for (let d = new Date(start); d <= end; d = addDays(d, 1)) range.push(new Date(d));
  return range;
});

const statusClass = (status: string) =>
  ({
    pending: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300",
    approved:
      "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300",
    rejected: "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300",
  }[status]);

// Modal
const openLeaveActionModal = (leave: UserLeave) => {
  selectedLeave.value = leave;
  showLeaveModal.value = true;
};
const closeLeaveModal = () => {
  selectedLeave.value = null;
  showLeaveModal.value = false;
};

/*
|--------------------------------------------------------------------------
| CALENDAR NAVIGATION
|--------------------------------------------------------------------------
*/
const currentMonth = ref(new Date());

const nextMonth = () => {
  currentMonth.value = addMonths(currentMonth.value, 1);
};

const prevMonth = () => {
  currentMonth.value = addMonths(currentMonth.value, -1);
};

/*
|--------------------------------------------------------------------------
| SHOW 2 MONTHS
|--------------------------------------------------------------------------
*/
const months = computed(() => [currentMonth.value, addMonths(currentMonth.value, 1)]);

const calendars = computed(() =>
  months.value.map((month) => {
    const start = startOfMonth(month);
    const end = endOfMonth(month);

    const days = eachDayOfInterval({
      start,
      end,
    });

    const leadingEmpty = Array(start.getDay()).fill(null);

    return [...leadingEmpty, ...days];
  })
);

// Date selection with overlap check
const onDateClick = (date: Date) => {
  const overlap = props.userLeaves.some((l) =>
    isWithinInterval(date, { start: parseISO(l.start_date), end: parseISO(l.end_date) })
  );
  if (overlap) {
    alert("Selected date overlaps with existing leave!");
    return;
  }

  if (selectedDates.value.length === 0) selectedDates.value.push(date);
  else if (selectedDates.value.length === 1) {
    if (date < selectedDates.value[0]) selectedDates.value.unshift(date);
    else selectedDates.value.push(date);
  } else selectedDates.value = [date];

  form.start_date = format(selectedDates.value[0], "yyyy-MM-dd");
  form.end_date = format(
    selectedDates.value[selectedDates.value.length - 1],
    "yyyy-MM-dd"
  );

  // Compute days excluding weekends/holidays
  let days = 0;
  for (
    let d = new Date(selectedDates.value[0]);
    d <= selectedDates.value[selectedDates.value.length - 1];
    d = addDays(d, 1)
  ) {
    const str = format(d, "yyyy-MM-dd");
    if (!isWeekend(d) && !props.holidays.includes(str)) days++;
  }
  form.days = days;

  if (days > 1) form.half_day = false;
  if (!isHoursManuallyEdited.value)
    form.hours = days === 1 && form.half_day ? 4 : days * 8;
};

const onCalendarClick = (day: Date) => {
  const leave = props.userLeaves.find((l) => isInLeaveRange(day, l));
  if (leave) openLeaveActionModal(leave);
  else onDateClick(day);
};

// Watches
watch(
  selectedDates,
  (newDates) => {
    if (!newDates.length) {
      form.start_date = "";
      form.end_date = "";
      form.days = 0;
      form.hours = 0;
      return;
    }
    const start = newDates[0];
    const end = newDates[newDates.length - 1];
    form.start_date = format(start, "yyyy-MM-dd");
    form.end_date = format(end, "yyyy-MM-dd");
    let days = 0;
    for (let d = new Date(start); d <= end; d = addDays(d, 1)) {
      const str = format(d, "yyyy-MM-dd");
      if (!isWeekend(d) && !props.holidays.includes(str)) days++;
    }
    form.days = days;
    if (days > 1) form.half_day = false;
    if (!isHoursManuallyEdited.value)
      form.hours = days === 1 && form.half_day ? 4 : days * 8;
  },
  { deep: true }
);

watch(
  () => form.half_day,
  (val) => {
    if (val) form.hours = 4;
    else if (form.days === 1) form.hours = 8;
  }
);

const markHoursEdited = () => {
  isHoursManuallyEdited.value = true;
};

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: "Leave Filing", href: route("leaves.create") },
];

// Submit
const submit = () => {
  form.post(route("leaves.store"), {
    onSuccess: () => {
      router.reload({ only: ["userLeaves", "balances"] });
      form.reset();
      selectedDates.value = [];
    },
  });
};

// Edit/Delete
const editLeave = (leave: UserLeave) => {
  form.start_date = leave.start_date;
  form.end_date = leave.end_date;
  selectedDates.value = [parseISO(leave.start_date), parseISO(leave.end_date)];
  showLeaveModal.value = false;
};

const deleteLeave = (leave: UserLeave) => {
  if (!leave.id) {
    alert("Leave ID missing");
    return;
  }
  if (!confirm("Delete this leave?")) return;
  router.delete(route("leaves.destroy", { leave: leave.id }), {
    onSuccess: () => (showLeaveModal.value = false),
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto p-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- LEFT: Form -->
        <div class="space-y-6">
          <h1 class="text-3xl font-bold mb-4 text-gray-900 dark:text-gray-100">
            File a Leave
          </h1>

          <form @submit.prevent="submit" class="space-y-4">
            <!-- Leave Type -->
            <div>
              <label
                class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300"
                >Leave Type</label
              >
              <select
                v-model="form.leave_type_id"
                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-blue-400 dark:focus:ring-blue-500 focus:outline-none"
              >
                <option value="">Select Leave Type</option>
                <option v-for="t in leaveTypes" :key="t.id" :value="t.id">
                  {{ t.name }}
                </option>
              </select>
              <p
                v-if="form.leave_type_id"
                class="mt-1 text-sm text-gray-600 dark:text-gray-400"
              >
                Available Balance:
                <span class="font-semibold">{{ getBalance(form.leave_type_id) }}</span>
              </p>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-4 max-w-md">
              <div>
                <label
                  class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >Start Date</label
                >
                <input
                  type="text"
                  v-model="form.start_date"
                  readonly
                  class="w-full rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2"
                />
              </div>
              <div>
                <label
                  class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >End Date</label
                >
                <input
                  type="text"
                  v-model="form.end_date"
                  readonly
                  class="w-full rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2"
                />
              </div>
            </div>

            <!-- Days & Hours -->
            <div class="grid grid-cols-2 gap-4 max-w-md">
              <div>
                <label
                  class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >Total Days</label
                >
                <input
                  type="number"
                  readonly
                  :value="form.days"
                  class="w-full rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2"
                />
              </div>
              <!-- Total Hours input -->
              <!-- Total Hours input -->
              <div>
                <label
                  class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300"
                >
                  Total Hours
                </label>
                <input
                  type="number"
                  min="0"
                  step="0.5"
                  v-model.number="form.hours"
                  @input="markHoursEdited"
                  :class="[
                    'w-full rounded border px-3 py-2 text-gray-900 dark:text-gray-100',
                    isExceedingBalance
                      ? 'border-red-500 bg-red-100 text-red-800 dark:border-red-500 dark:bg-red-900 dark:text-red-200'
                      : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800',
                  ]"
                />
                <p
                  v-if="isExceedingBalance"
                  class="text-red-600 dark:text-red-200 text-sm mt-1"
                >
                  Warning: Entered hours exceed your available leave balance ({{
                    maxHours
                  }}
                  hours)
                </p>
              </div>
            </div>

            <!-- Half Day Checkbox -->
            <div v-if="form.days === 1" class="flex items-center space-x-2">
              <input type="checkbox" v-model="form.half_day" id="halfDay" />
              <label for="halfDay" class="text-gray-700 dark:text-gray-300"
                >Half Day</label
              >
            </div>

            <!-- Reason -->
            <div>
              <label
                class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300"
                >Reason</label
              >
              <textarea
                v-model="form.reason"
                rows="3"
                placeholder="Optional reason"
                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2"
              />
            </div>

            <!-- Attachment -->
            <div>
              <label
                class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300"
                >Attachment (optional)</label
              >
              <input
                type="file"
                @change="handleFile"
                class="block w-full text-sm text-gray-900 dark:text-gray-100"
              />
            </div>

            <!-- Submit & Reset -->
            <div class="pt-4 flex space-x-4">
              <button
                type="submit"
                :disabled="form.processing"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition"
              >
                {{ form.processing ? "Submitting…" : "Submit Leave" }}
              </button>
              <button
                type="button"
                @click="resetForm"
                class="px-6 py-2 bg-gray-400 hover:bg-gray-500 text-white font-semibold rounded transition"
              >
                Reset
              </button>
            </div>
          </form>
        </div>

        <!-- RIGHT: Calendar -->
        <!-- RIGHT: Calendar -->
        <div>
          <!-- NAVIGATION -->
          <div class="flex items-center justify-between mb-4">
            <button
              @click="prevMonth"
              class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600"
            >
              ← Previous
            </button>

            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">
              {{ format(currentMonth, "MMMM yyyy") }}
            </h2>

            <button
              @click="nextMonth"
              class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600"
            >
              Next →
            </button>
          </div>
          <div
            v-for="(month, idx) in calendars"
            :key="idx"
            class="mb-6 p-4 rounded-lg ring-1 ring-gray-300 dark:ring-gray-600"
          >
            <h2 class="font-semibold mb-2 text-gray-800 dark:text-gray-100 text-center">
              {{
                            format(month.find(d => d) as Date, 'MMMM yyyy')}}
            </h2>
            <div class="grid grid-cols-7 text-center text-sm gap-1">
              <div
                v-for="d in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']"
                :key="d"
                class="font-bold text-gray-700 dark:text-gray-300"
              >
                {{ d }}
              </div>
              <div
                v-for="(day, i) in month"
                :key="i"
                class="h-10 flex items-center justify-center rounded cursor-pointer text-sm transition-all"
                :class="{
                  /* Selected range (new filing) */
                  'bg-blue-600 text-white':
                    day && dateRange.some((sd) => isSameDay(sd, day)),

                  /* Approved leave */
                  'bg-green-600 text-white':
                    day &&
                    !dateRange.some((sd) => isSameDay(sd, day)) &&
                    isApprovedLeave(day),

                  /* Pending leave */
                  'bg-orange-500 text-orange-900':
                    day &&
                    !dateRange.some((sd) => isSameDay(sd, day)) &&
                    !isApprovedLeave(day) &&
                    isPendingLeave(day),

                  /* Holiday */
                  'bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-100':
                    day && props.holidays.includes(format(day, 'yyyy-MM-dd')),

                  /* Weekend */
                  'text-gray-400 dark:text-gray-500': day && isWeekend(day),

                  /* Hover */
                  'hover:bg-blue-200 dark:hover:bg-blue-800': day,
                }"
                @click="day && onCalendarClick(day)"
              >
                {{ day ? format(day, "d") : "" }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showLeaveModal"
      class="fixed inset-0 bg-black/40 flex items-center justify-center"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-96 space-y-4">
        <h3 class="text-lg font-bold">Leave Details</h3>

        <p>{{ selectedLeave?.start_date }} → {{ selectedLeave?.end_date }}</p>

        <span
          class="px-2 py-1 rounded text-sm"
          :class="{
            'bg-green-600 text-white': selectedLeave?.status === 'approved',
            'bg-orange-500 text-white': selectedLeave?.status === 'pending',
          }"
        >
          {{ selectedLeave?.status }}
        </span>

        <div class="flex justify-end space-x-2">
          <button
            v-if="selectedLeave?.status === 'pending'"
            @click="editLeave(selectedLeave)"
            class="px-4 py-2 bg-blue-600 text-white rounded"
          >
            Edit
          </button>

          <button
            v-if="selectedLeave?.status === 'pending'"
            @click="deleteLeave(selectedLeave)"
            class="px-4 py-2 bg-red-600 text-white rounded"
          >
            Delete
          </button>

          <button @click="showLeaveModal = false" class="px-4 py-2 bg-gray-400 rounded">
            Close
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
