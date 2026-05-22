<script setup lang="ts">
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

type Overtime = {
  id: number;
  hours: number;
  status: string;
};

type Adjustment = {
  id: number;
  adjusted_hours: number;
  time_in: string | null;
  time_out: string | null;

  shiftcode?: {
    shiftCode: string;
  };
};

type Timekeeping = {
  id: number;
  dated: string;
  dayType: string;
  shiftCode: string;
  timeIn: string | null;
  timeOut: string | null;
  hoursWorked: number;
  overtime: number;
  typeCode: string;

  // relationships
  overtimes?: Overtime[];
  adjustment?: Adjustment | null;
};

const page = usePage();

const rows = computed(() => page.props.timekeeping as Timekeeping[]);
const empnum = computed(() => page.props.empnum as string);

function formatDate(value?: string | null) {
  if (!value) return "-";

  return new Date(value).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "2-digit",
  });
}

function formatTime(value?: string | null) {
  if (!value) return "-";

  if (value.includes(" ")) {
    return value.split(" ")[1]?.slice(0, 8) ?? "-";
  }

  if (value.includes("T")) {
    return value.split("T")[1]?.slice(0, 8) ?? "-";
  }

  return value.slice(0, 8);
}
</script>

<template>
  <Head :title="`Timekeeping ${empnum}`" />

  <div
    class="p-4 min-h-screen bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100"
  >
    <div class="mb-4">
      <h1 class="text-xl font-bold">Timekeeping Details</h1>

      <div class="text-sm text-gray-500 dark:text-gray-400">Employee #: {{ empnum }}</div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
      <table class="w-full text-sm border-collapse">
        <thead class="bg-gray-100 dark:bg-gray-800">
          <tr>
            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Date
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Day Type
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Shift
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Time In
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Time Out
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Hours
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Type
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Filed OT
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              OT Status
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Adjustment
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Adjusted Shift
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Adjusted Time In
            </th>

            <th class="border border-gray-300 dark:border-gray-700 px-3 py-2 text-left">
              Adjusted Time Out
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="row in rows"
            :key="row.id"
            class="hover:bg-gray-50 dark:hover:bg-gray-800 transition"
          >
            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              {{ formatDate(row.dated) }}
            </td>

            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              {{ row.dayType }}
            </td>

            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              {{ row.shiftCode }}
            </td>

            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              {{ formatTime(row.timeIn) }}
            </td>

            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              {{ formatTime(row.timeOut) }}
            </td>

            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              {{ row.hoursWorked }}
            </td>

            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              {{ row.typeCode }}
            </td>
            <!-- Filed OT -->
            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              <div v-if="row.overtimes?.length" class="space-y-1">
                <div v-for="ot in row.overtimes" :key="ot.id">{{ ot.hours }} hrs</div>
              </div>

              <span v-else class="text-gray-400"> - </span>
            </td>

            <!-- OT Status -->
            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              <div v-if="row.overtimes?.length" class="space-y-1">
                <div
                  v-for="ot in row.overtimes"
                  :key="ot.id"
                  :class="{
                    'text-green-600 dark:text-green-400': ot.status === 'Approved',

                    'text-yellow-600 dark:text-yellow-400': ot.status === 'Pending',

                    'text-red-600 dark:text-red-400': ot.status === 'Rejected',
                  }"
                  class="font-medium"
                >
                  {{ ot.status }}
                </div>
              </div>

              <span v-else class="text-gray-400"> - </span>
            </td>

            <!-- Adjustment Hours -->
            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              <span
                v-if="row.adjustment"
                class="text-blue-600 dark:text-blue-400 font-medium"
              >
                {{ row.adjustment.adjusted_hours ?? 0 }}
              </span>

              <span v-else class="text-gray-400"> - </span>
            </td>

            <!-- Adjusted Shift -->
            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              <span v-if="row.adjustment">
                {{ row.adjustment.shiftcode?.shiftCode ?? "-" }}
              </span>

              <span v-else class="text-gray-400"> - </span>
            </td>

            <!-- Adjusted Time In -->
            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              <span v-if="row.adjustment">
                {{ formatTime(row.adjustment.time_in) }}
              </span>

              <span v-else class="text-gray-400"> - </span>
            </td>

            <!-- Adjusted Time Out -->
            <td class="border border-gray-300 dark:border-gray-700 px-3 py-2">
              <span v-if="row.adjustment">
                {{ formatTime(row.adjustment.time_out) }}
              </span>

              <span v-else class="text-gray-400"> - </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
