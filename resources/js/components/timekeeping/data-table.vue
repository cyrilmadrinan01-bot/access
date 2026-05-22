<script setup lang="ts" generic="TData, TValue">
import { ref, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  FlexRender,
} from "@tanstack/vue-table";
import type { ColumnDef, SortingState, VisibilityState } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { ChevronDown } from "lucide-vue-next";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { valueUpdater } from "@/lib/utils";
import {
  Select,
  SelectContent,
  SelectTrigger,
  SelectValue,
  SelectItem,
} from "@/components/ui/select";

const props = defineProps<{
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  currentPayrollDate: string | null;
}>();

// ----- Table State -----
const sorting = ref<SortingState>([]);
const columnVisibility = ref<VisibilityState>({});

const table = useVueTable({
  get data() {
    return props.data;
  },
  get columns() {
    return props.columns;
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
  onColumnVisibilityChange: (updaterOrValue) =>
    valueUpdater(updaterOrValue, columnVisibility),
  state: {
    get sorting() {
      return sorting.value;
    },
    get columnVisibility() {
      return columnVisibility.value;
    },
  },
});

// ----- Payroll Cutoff Filter -----
type PayrollCutOff = {
  id: number;
  cutOffStart: string;
  cutOffEnd: string;
  payrollDate: string;
  current: string;
};

const page = usePage();
const cutoffs = ref<PayrollCutOff[]>([]);
const selectedCutoff = ref<number | null>(null);

// Initialize cutoffs and auto-select current
watch(
  () => page.props.cutoffs,
  (val) => {
    cutoffs.value = val as PayrollCutOff[];
    if (!selectedCutoff.value) {
      const current = cutoffs.value.find((c) => c.current === "Yes");
      selectedCutoff.value = current?.id ?? null;
    }
  },
  { immediate: true }
);

// Watch for external selectedCutoff (from server)
watch(
  () => page.props.selectedCutoff,
  (val) => {
    selectedCutoff.value = val as number;
  },
  { immediate: true }
);

// Change cutoff
watch(selectedCutoff, (val) => {
  if (!val) return;
  router.get(
    route("timekeeping"),
    { cutoff_id: val },
    { preserveScroll: true, replace: true }
  );
});

// ----- Helpers -----
function formatISOToDate(dateTime: string | null | undefined) {
  if (!dateTime) return "—";
  const clean = dateTime.split(".")[0].replace("T", " ");
  const [date, time] = clean.split(" ");
  if (!date || !time) return dateTime;
  const [year, month, day] = date.split("-");
  return `${month}-${day}-${year}`;
}
</script>

<template>
  <!-- Payroll Cutoff Filter -->
  <div class="flex items-center justify-between py-2">
    <!-- Left side: Payroll Cutoff -->
    <div class="flex items-center space-x-2">
      <Label class="text-sm font-medium whitespace-nowrap">Payroll Cutoff:</Label>
      <Select v-model="selectedCutoff" class="w-44">
        <SelectTrigger
          class="px-2 py-1 border rounded text-sm bg-white dark:bg-gray-800 dark:text-gray-100"
        >
          <SelectValue placeholder="Select" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="cutoff in cutoffs" :key="cutoff.id" :value="cutoff.id">
            {{ formatISOToDate(cutoff.cutOffStart) }} –
            {{ formatISOToDate(cutoff.cutOffEnd) }}
            <span
              v-if="cutoff.current === 'Yes'"
              class="font-semibold text-blue-600 dark:text-blue-400"
              >(Current)</span
            >
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <!-- Right side: Columns -->
    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button variant="outline" class="text-sm">
          Columns
          <ChevronDown class="w-4 h-4 ml-1" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent
        align="end"
        class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
      >
        <DropdownMenuCheckboxItem
          v-for="column in table.getAllColumns().filter((c) => c.getCanHide())"
          :key="column.id"
          :modelValue="column.getIsVisible()"
          @update:modelValue="(val) => column.toggleVisibility(!!val)"
          class="capitalize text-sm"
        >
          {{ column.id }}
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>

  <!-- Data Table -->
  <div class="rounded-md border border-gray-300 dark:border-gray-700 overflow-x-auto">
    <table class="w-full table-auto">
      <thead>
        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            class="text-left p-2 bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-sm font-semibold border-b border-gray-300 dark:border-gray-600"
          >
            <FlexRender
              :render="header.column.columnDef.header"
              :props="header.getContext()"
            />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in table.getRowModel().rows"
          :key="row.id"
          class="transition duration-200 ease-in-out transform hover:bg-gray-300 dark:hover:bg-gray-700 hover:scale-[1.01] hover:shadow-md cursor-pointer odd:bg-gray-100 dark:odd:bg-gray-900 even:bg-white dark:even:bg-gray-800"
        >
          <td
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            class="p-2 text-gray-900 dark:text-gray-100"
          >
            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
