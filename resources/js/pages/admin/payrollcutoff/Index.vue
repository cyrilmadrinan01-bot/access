<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import { route } from 'ziggy-js'
import { ref, reactive } from 'vue'

defineProps<{
  cutoffs: {
    id: number
    cutOffStart: string
    cutOffEnd: string
    payrollDate: string
    current: string
    lockDate: string
    lockTime: string
    hasRunTimekeeping?: boolean // <-- new optional property
  }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Payroll Cutoff Management',
    href: route('admin.payroll-cutoff.index'),
  },
]

const showCreateModal = ref(false)

const form = reactive({
  cutOffStart: '',
  cutOffEnd: '',
  payrollDate: '',
  current: 'No',
  lockDate: '',
  lockTime: '',
})

function submitCutoff() {
  router.post(
    route('admin.payroll-cutoff.store'),
    form,
    {
      preserveScroll: true,
      onSuccess: () => {
        showCreateModal.value = false

        form.cutOffStart = ''
        form.cutOffEnd = ''
        form.payrollDate = ''
        form.current = 'No'
        form.lockDate = ''
        form.lockTime = ''
      },
    }
  )
}

function runGeneration(id: number, alreadyRun: boolean) {
  const msg = alreadyRun
    ? 'Re-run timekeeping for this cutoff?'
    : 'Generate timekeeping for this cutoff?'
  if (!confirm(msg)) return
  router.post(`/admin/payroll-cutoff/${id}/generate-timekeeping`)
}

function processTimekeeping(id: number) {
  router.post(route('timekeeping.process.run', id), {}, {
    preserveScroll: true,
  });
}

function formatISOToDate(dateTime: string | null | undefined) {
  if (!dateTime) return '—'
  const clean = dateTime.split('.')[0].replace('T', ' ')
  const [date, time] = clean.split(' ')
  if (!date || !time) return dateTime
  const [year, month, day] = date.split('-')
  return `${month}-${day}-${year}`
}
</script>

<template>
  <Head title="Payroll Cutoff Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Payroll Cutoff Management</h1>

        <button
          @click="showCreateModal = true"
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"
        >
          Add Cutoff
        </button>
      </div>

      <div
        class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
      >
        <table class="w-full text-left border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
              <th class="px-4 py-2 border-b">Cutoff</th>
              <th class="px-4 py-2 border-b">Payroll Date</th>
              <th class="px-4 py-2 border-b">Status</th>
              <th class="px-4 py-2 border-b">Lock Date</th>
              <th class="px-4 py-2 border-b">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="cutoff in cutoffs"
              :key="cutoff.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-4 py-2 border-b">
                {{ formatISOToDate(cutoff.cutOffStart) }} –
                {{ formatISOToDate(cutoff.cutOffEnd) }}
              </td>
              <td class="px-4 py-2 border-b">
                {{ formatISOToDate(cutoff.payrollDate) }}
              </td>
              <td class="px-4 py-2 border-b">
                <span
                  v-if="cutoff.current === 'Yes'"
                  class="text-green-600 font-semibold"
                >
                  Current
                </span>
              </td>
              <td class="px-4 py-2 border-b">
                {{ formatISOToDate(cutoff.lockDate) }}
                {{ formatISOToDate(cutoff.lockTime) }}
              </td>
              <td class="px-4 py-2 border-b">
                <button
                  class="px-3 py-1 rounded text-white"
                  :class="
                    cutoff.hasRunTimekeeping
                      ? 'bg-yellow-600 hover:bg-yellow-700'
                      : 'bg-blue-600 hover:bg-blue-700'
                  "
                  @click="runGeneration(cutoff.id, cutoff.hasRunTimekeeping ?? false)"
                >
                  {{
                    cutoff.hasRunTimekeeping ? "Re-run Timekeeping" : "Run Timekeeping"
                  }}
                </button>
              </td>
            </tr>

            <tr v-if="cutoffs.length === 0">
              <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                No payroll cutoffs available.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE MODAL -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    >
      <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Add Payroll Cutoff</h2>

          <button
            @click="showCreateModal = false"
            class="text-gray-500 hover:text-red-500"
          >
            ✕
          </button>
        </div>

        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="block text-sm mb-1"> Cutoff Start </label>

            <input
              v-model="form.cutOffStart"
              type="date"
              class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800"
            />
          </div>

          <div>
            <label class="block text-sm mb-1"> Cutoff End </label>

            <input
              v-model="form.cutOffEnd"
              type="date"
              class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800"
            />
          </div>

          <div>
            <label class="block text-sm mb-1"> Payroll Date </label>

            <input
              v-model="form.payrollDate"
              type="date"
              class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800"
            />
          </div>

          <div>
            <label class="block text-sm mb-1"> Current </label>

            <select
              v-model="form.current"
              class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800"
            >
              <option value="No">No</option>
              <option value="Yes">Yes</option>
            </select>
          </div>

          <div>
            <label class="block text-sm mb-1"> Lock Date </label>

            <input
              v-model="form.lockDate"
              type="date"
              class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800"
            />
          </div>

          <div>
            <label class="block text-sm mb-1"> Lock Time </label>

            <input
              v-model="form.lockTime"
              type="time"
              class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800"
            />
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
          <button @click="showCreateModal = false" class="px-4 py-2 rounded-lg border">
            Cancel
          </button>

          <button
            @click="submitCutoff"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
          >
            Save Cutoff
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
