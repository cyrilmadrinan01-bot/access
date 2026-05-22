<script setup lang="ts">
import { computed } from "vue";
import { route } from "ziggy-js";

type Cutoff = {
  id: number;
  cutOffStart: string;
  cutOffEnd: string;
  payrollDate: string;
};

type TimekeepingRow = {
  id: number;
  empnum: string;
  reg: number;
  nsd_reg: number;
  overtime_reg: number;
  overtime_nsd_reg: number;
  overtime_lh: number;
  overtime_lh_8: number;
  overtime_lh_12: number;
  overtime_nsd_lh: number;
  overtime_sh: number;
  overtime_sh_8: number;
  overtime_sh_12: number;
  overtime_nsd_sh: number;
  overtime_lhrd: number;
  overtime_lhrd_8: number;
  overtime_lhrd_12: number;
  overtime_nsd_lhrd: number;
  overtime_shrd: number;
  overtime_shrd_8: number;
  overtime_shrd_12: number;
  overtime_nsd_shrd: number;
  overtime_rd: number;
  overtime_rd_8: number;
  overtime_rd_12: number;
  overtime_nsd_rd: number;
  late_reg: number;
  undertime: number;
  absent: number;
  adjusted_hours: number;
  adjusted_nsd: number;
  adjusted_ot_hours: number;
  adjusted_ot_nsd: number;
};

const props = defineProps<{
  cutoffId: number | null;
  cutoffs: Cutoff[];
  timekeepingRows?: TimekeepingRow[];
}>();

const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "run", cutoffId: number): void;
  (e: "next"): void;
}>();

// v-model bridge
const localCutoff = computed({
  get: () => props.cutoffId,
  set: (val) => emit("update:cutoffId", val ? Number(val) : null),
});

const hasData = computed(() => (props.timekeepingRows?.length ?? 0) > 0);

// RUN PROCESS
function runProcess() {
  if (!localCutoff.value) {
    alert("Please select a payroll cutoff.");
    return;
  }

  emit("run", localCutoff.value);
}

// NEXT STEP
function goNext() {
  emit("next");
}

// FORMAT
function formatDate(dateTime: string) {
  if (!dateTime) return "—";
  const [date] = dateTime.split("T");
  const [y, m, d] = date.split("-");
  return `${m}-${d}-${y}`;
}

function openTimekeeping(timekeeping: any) {
  const url = route("timekeeping.details", {
    cutoff: localCutoff.value,
    empnum: timekeeping.empnum,
  });

  window.open(
    url,
    "_blank",
    "width=1200,height=700,toolbar=no,menubar=no,scrollbars=yes,resizable=yes"
  );
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="text-xl font-bold">Step 1: Process Timekeeping</h2>

    <!-- Controls -->
    <div class="flex items-end gap-4">
      <!-- RUN -->
      <button
        class="px-4 py-2 text-white rounded bg-blue-600"
        :class="hasData ? 'bg-yellow-600' : 'bg-blue-600'"
        @click="runProcess"
      >
        {{ hasData ? "Re-run Process" : "Run Process" }}
      </button>

      <!-- NEXT -->
      <button class="px-4 py-2 bg-green-600 text-white rounded" @click="goNext">
        Next
      </button>
    </div>

    <!-- TABLE -->
    <div v-if="timekeepingRows?.length">
      <h2 class="text-xl font-semibold mb-2">Timekeeping Summary</h2>

      <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-3 py-2 border">Emp #</th>
              <th class="px-3 py-2 border">Reg</th>
              <th class="px-3 py-2 border">NSD</th>
              <th class="px-3 py-2 border">OT</th>
              <th class="px-3 py-2 border">OT NSD</th>
              <th class="px-3 py-2 border">OT LH</th>
              <th class="px-3 py-2 border">OT LH 8H</th>
              <th class="px-3 py-2 border">OT LH 12H</th>
              <th class="px-3 py-2 border">OT LHRD</th>
              <th class="px-3 py-2 border">OT LHRD 8H</th>
              <th class="px-3 py-2 border">OT LHRD 12H</th>
              <th class="px-3 py-2 border">OT SH</th>
              <th class="px-3 py-2 border">OT SH 8H</th>
              <th class="px-3 py-2 border">OT SH 12H</th>
              <th class="px-3 py-2 border">OT SHRD</th>
              <th class="px-3 py-2 border">OT SHRD 8H</th>
              <th class="px-3 py-2 border">OT SHRD 12H</th>
              <th class="px-3 py-2 border">OT RD</th>
              <th class="px-3 py-2 border">OT RD 8H</th>
              <th class="px-3 py-2 border">OT RD 12H</th>
              <th class="px-3 py-2 border">Late</th>
              <th class="px-3 py-2 border">UT</th>
              <th class="px-3 py-2 border">Absent</th>
              <th class="px-3 py-2 border">Adjustment</th>
              <th class="px-3 py-2 border">Adjustment NSD</th>
              <th class="px-3 py-2 border">Adjustment OT</th>
              <th class="px-3 py-2 border">Adjustment OT NSD</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in timekeepingRows"
              :key="row.id"
              @click="openTimekeeping(row)"
              class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
            >
              <td class="px-3 py-2 border">{{ row.empnum }}</td>
              <td class="px-3 py-2 border">{{ row.reg }}</td>
              <td class="px-3 py-2 border">{{ row.nsd_reg }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_reg }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_nsd_reg }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_lh }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_lh_8 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_lh_12 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_lhrd }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_lhrd_8 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_lhrd_12 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_sh }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_sh_8 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_sh_12 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_shrd }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_shrd_8 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_shrd_12 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_rd }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_rd_8 }}</td>
              <td class="px-3 py-2 border">{{ row.overtime_rd_12 }}</td>
              <td class="px-3 py-2 border">{{ row.late_reg }}</td>
              <td class="px-3 py-2 border">{{ row.undertime }}</td>
              <td class="px-3 py-2 border">{{ row.absent }}</td>
              <td class="px-3 py-2 border">{{ row.adjusted_hours }}</td>
              <td class="px-3 py-2 border">{{ row.adjusted_nsd }}</td>
              <td class="px-3 py-2 border">{{ row.adjusted_ot_hours }}</td>
              <td class="px-3 py-2 border">{{ row.adjusted_ot_nsd }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="text-gray-500">No timekeeping data yet.</div>
  </div>
</template>
