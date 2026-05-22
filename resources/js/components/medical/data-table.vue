<script setup lang="ts" generic="TData, TValue">
import { MoreHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { router } from '@inertiajs/vue3'
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'

import { ArrowUpDown, ChevronDown } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { h, ref, watch } from 'vue'

import {
  FlexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getFilteredRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'

import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import { valueUpdater } from '@/lib/utils'

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]
  data: TData[]
}>()

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const pageSize = ref(10)        // default page size
const pageIndex = ref(0)        // current page index

const table = useVueTable({
  get data() { return props.data },
  get columns() { return props.columns },
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),

  getSortedRowModel: getSortedRowModel(),
  onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
  onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters), getFilteredRowModel: getFilteredRowModel(),
  onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get pagination() { return { pageIndex: pageIndex.value, pageSize: pageSize.value } },
  },
})

const nextPage = () => {
  pageIndex.value++
  table.setPageIndex(pageIndex.value)
}

const prevPage = () => {
  pageIndex.value--
  table.setPageIndex(pageIndex.value)
}

const isAdding = ref(false)
const handleAdd = () => {
  isAdding.value = true
  setTimeout(() => {
    router.visit('/medical/create')
    isAdding.value = false
  }, 1000)
}

// Watch pageSize and reset pageIndex
watch(pageSize, () => pageIndex.value = 0)
</script>

<template>
  <div class="flex items-center py-4">
  <div class="flex items-center gap-4">
    <Input class="max-w-sm" placeholder="Filter request ID..."
      :model-value="table.getColumn('reqid')?.getFilterValue() as string"
      @update:model-value=" table.getColumn('reqid')?.setFilterValue($event)" />

    <Button @click="handleAdd" :disabled="isAdding" class="bg-blue-500 text-white dark:bg-blue-400 dark:text-gray-900 hover:bg-blue-600 dark:hover:bg-blue-500">
      <span v-if="!isAdding">Add</span>
      <span v-else>Loading...</span>
    </Button>
    </div>

    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button variant="outline" class="ml-auto">
          Columns
          <ChevronDown class="w-4 h-4 ml-2" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuCheckboxItem v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
          :key="column.id" class="capitalize" :modelValue="column.getIsVisible()" @update:modelValue="(value) => {
            column.toggleVisibility(!!value)
          }">
          {{ column.id }}
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
  <div class="rounded-md border overflow-hidden max-h-[600px]">
    <table class="w-full table-fixed border-collapse">
      <!-- Table Header -->
      <thead class="sticky top-0 z-10">
        <tr v-for="(headerGroup, i) in table.getHeaderGroups()" :key="headerGroup.id">
          <th v-for="(header, j) in headerGroup.headers" :key="header.id" class="text-left p-2 uppercase text-sm font-semibold border-b border-gray-300
                 bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100" :class="{
                  'rounded-tl-md': i === 0 && j === 0,
                  'rounded-tr-md': i === 0 && j === headerGroup.headers.length - 1
                }">
            <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
          </th>
        </tr>
      </thead>

      <!-- Table Body -->
      <tbody>
        <tr v-for="row in table.getRowModel().rows" :key="row.id" class="transition duration-200 ease-in-out transform hover:bg-gray-300 dark:hover:bg-gray-700 hover:scale-[1.01] hover:shadow-md cursor-pointer
           odd:bg-gray-100 dark:odd:bg-gray-900
           even:bg-white dark:even:bg-gray-800">
          <td v-for="cell in row.getVisibleCells()" :key="cell.id" class="p-2 text-gray-900 dark:text-gray-100">
            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination controls -->
    <div
      class="flex items-center justify-between py-4 px-2 space-x-2 bg-gray-100 dark:bg-gray-900 border-t border-gray-300 dark:border-gray-700">
      <!-- Prev / Next buttons + page info -->
      <div class="flex items-center space-x-2 text-gray-900 dark:text-gray-100">
        <Button :disabled="!table.getCanPreviousPage()" @click="prevPage">Previous</Button>
        <Button :disabled="!table.getCanNextPage()" @click="nextPage">Next</Button>
        <span>Page {{ pageIndex + 1 }} of {{ table.getPageCount() }}</span>
      </div>

      <!-- Page size selector -->
      <div class="flex items-center space-x-2 text-gray-900 dark:text-gray-100">
        <span>Rows per page:</span>
        <select v-model="pageSize"
          class="border rounded p-1 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200">
          <option v-for="size in [5, 10, 20, 50]" :key="size" :value="size">{{ size }}</option>
        </select>
      </div>
    </div>
  </div>

</template>