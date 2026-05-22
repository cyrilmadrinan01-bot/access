<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, computed } from 'vue'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
    pendingOvertimes?: any[]
    approvedOvertimes?: any[]
    rejectedOvertimes?: any[]
}>()

const pendingOvertimes = computed(() => props.pendingOvertimes || [])
const approvedOvertimes = computed(() => props.approvedOvertimes || [])
const rejectedOvertimes = computed(() => props.rejectedOvertimes || [])

const activeTab = ref<'pending' | 'approved' | 'rejected'>('pending')
const selected = ref<any | null>(null)
const rejectReason = ref('')
const selectedIds = ref<number[]>([])
const errorMessage = ref('') 

function toggle(id: number) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter(i => i !== id)
  } else {
    selectedIds.value.push(id)
  }
}

function toggleAll(e: Event) {
  if (activeTab.value !== 'pending') return

  const checked = (e.target as HTMLInputElement).checked

  selectedIds.value = checked
    ? pendingOvertimes.value.map(c => c.id)
    : []
}

function bulkApprove() {
  errorMessage.value = ''
  router.post(
    route('approvals.overtime.bulk-approve'),
    { ids: selectedIds.value },
    {
      onSuccess: () => {
        selectedIds.value = []
      },
      onError: (errors) => {
        if (errors.overtime) errorMessage.value = errors.overtime
      },
    }
  )
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Approval', href: '' },
    { title: 'Overtimes', href: route('approvals.timekeeping') },
]

// PENDING ACTIONS
function approve(id: number) {
  errorMessage.value = ''
  router.post(
    route('approvals.overtime.approve', id),
    {},
    {
      onSuccess: () => close(),
      onError: (errors) => {
        if (errors.overtime) errorMessage.value = errors.overtime
      },
    }
  )
}

function reject(id: number) {
  errorMessage.value = ''
  router.post(
    route('approvals.overtime.reject', { overtime: id }),
    { reason: rejectReason.value },
    {
      onSuccess: () => close(),
      onError: (errors) => {
        if (errors.overtime) errorMessage.value = errors.overtime
      },
    }
  )
}


// APPROVED ACTIONS
function cancelApproval(id: number) {
  errorMessage.value = ''
  router.post(route('approvals.overtime.cancel', id), {}, { onSuccess: () => close() })
}

function cancelRejection(id: number) {
  errorMessage.value = ''
  router.post(
    route('approvals.overtime.cancel-rejection', id),
    {},
    {
      onSuccess: () => close(),
      onError: (errors) => {
        if (errors.overtime) errorMessage.value = errors.overtime
      },
    }
  )
}


// Modal control
function open(c: any) {
  selected.value = c
  errorMessage.value = ''
}
function close() {
  selected.value = null
  rejectReason.value = ''
  errorMessage.value = ''
}

function formatISOToCustom(dateTime: string | null | undefined) {
  if (!dateTime) return '—'
  // Remove the fractional seconds and Z
  const clean = dateTime.split('.')[0].replace('T', ' ')
  const [date, time] = clean.split(' ')
  if (!date || !time) return dateTime
  const [year, month, day] = date.split('-')
  return `${month}-${day}-${year} ${time}`
}

function formatISOToDate(dateTime: string | null | undefined) {
  if (!dateTime) return '—'
  // Remove the fractional seconds and Z
  const clean = dateTime.split('.')[0].replace('T', ' ')
  const [date, time] = clean.split(' ')
  if (!date || !time) return dateTime
  const [year, month, day] = date.split('-')
  return `${month}-${day}-${year}`
}

</script>

<template>
<AppLayout :breadcrumbs="breadcrumbs">
  <div class="p-6">
    <h1 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">
      Overtimes
    </h1>

    <!-- TABS -->
    <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
      <button
        class="px-4 py-2 font-medium"
        :class="activeTab==='pending' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 dark:text-gray-400'"
        @click="activeTab='pending'">
        Pending
      </button>
      <button
        class="px-4 py-2 font-medium"
        :class="activeTab==='approved' ? 'border-b-2 border-yellow-600 text-yellow-600' : 'text-gray-500 dark:text-gray-400'"
        @click="activeTab='approved'">
        Approved
      </button>
      <button
        class="px-4 py-2 font-medium"
        :class="activeTab==='rejected' ? 'border-b-2 border-yellow-600 text-yellow-600' : 'text-gray-500 dark:text-gray-400'"
        @click="activeTab='rejected'">
        Rejected
      </button>
    </div>

    <!-- PENDING TAB -->
    <div v-if="activeTab === 'pending'" class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto">
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
                  pendingOvertimes.length > 0 &&
                  selectedIds.length === pendingOvertimes.length
                "
                @change="toggleAll"
              />
            </th>
            <th class="p-3 text-left">Employee Number</th>
            <th class="p-3 text-left">Employee</th>
            <th class="p-3 text-left">Date</th>
            <th class="p-3 text-left">Start Time</th>
            <th class="p-3 text-left">End Time</th>
            <th class="p-3 text-left">Overtime Reason</th>
            <th class="p-3 text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in pendingOvertimes" :key="c.id" class="border-t dark:border-gray-700">
            <td class="p-3 text-center">
              <input
                type="checkbox"
                :checked="selectedIds.includes(c.id)"
                @change="toggle(c.id)"
              />
            </td>
            <td class="p-3">{{ c.creator?.empnum || '—' }}</td>
            <td class="p-3">{{ c.creator?.name || '—' }}</td>
            <td class="p-3">{{ formatISOToDate(c.timekeeping?.dated) || '—' }}</td>
            <td class="p-3">{{ formatISOToCustom(c.startTime) || '—' }}</td>
            <td class="p-3">{{ formatISOToCustom(c.endTime) || '—' }}</td>
            <td class="p-3">{{ c.reasons || '—' }}</td>
            <td class="p-3 text-center">
              <button class="text-blue-600 dark:text-blue-400" @click="open(c)">Review</button>
            </td>
          </tr>
          <tr v-if="!pendingOvertimes.length">
            <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">No pending Overtimes 🎉</td>
          </tr>
        </tbody>
      </table>
    </div> 

    <!-- APPROVED TAB -->
    <div v-if="activeTab === 'approved'" class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-700">
          <tr>
            <th class="p-3 text-left">Employee Number</th>
            <th class="p-3 text-left">Employee</th>
            <th class="p-3 text-left">Date</th>
            <th class="p-3 text-left">Start Time</th>
            <th class="p-3 text-left">End Time</th>
            <th class="p-3 text-left">Overtime Reason</th>
            <th class="p-3 text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in approvedOvertimes" :key="c.id" class="border-t dark:border-gray-700">
            <td class="p-3">{{ c.creator?.empnum || '—' }}</td>
            <td class="p-3">{{ c.creator?.name || '—' }}</td>
            <td class="p-3">{{ formatISOToDate(c.timekeeping?.dated) || '—' }}</td>
            <td class="p-3">{{ formatISOToCustom(c.startTime) || '—' }}</td>
            <td class="p-3">{{ formatISOToCustom(c.endTime) || '—' }}</td>
            <td class="p-3">{{ c.reasons || '—' }}</td>
            <td class="p-3 text-center">
              <button class="text-yellow-600 dark:text-yellow-400" @click="open(c)">Cancel Approval</button>
            </td>
          </tr>
          <tr v-if="!approvedOvertimes.length">
            <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">No approved Overtimes 🎉</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- REJECTED TAB -->
    <div v-if="activeTab === 'rejected'" class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-700">
          <tr>
            <th class="p-3 text-left">Employee Number</th>
            <th class="p-3 text-left">Employee</th>
            <th class="p-3 text-left">Date</th>
            <th class="p-3 text-left">Start Time</th>
            <th class="p-3 text-left">End Time</th>
            <th class="p-3 text-left">Overtime Reason</th>
            <th class="p-3 text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in rejectedOvertimes" :key="c.id" class="border-t dark:border-gray-700">
            <td class="p-3">{{ c.creator?.empnum || '—' }}</td>
            <td class="p-3">{{ c.creator?.name || '—' }}</td>
            <td class="p-3">{{ formatISOToDate(c.timekeeping?.dated) || '—' }}</td>
            <td class="p-3">{{ formatISOToCustom(c.startTime) || '—' }}</td>
            <td class="p-3">{{ formatISOToCustom(c.endTime) || '—' }}</td>
            <td class="p-3">{{ c.reasons || '—' }}</td>
            <td class="p-3 text-center">
              <button class="text-yellow-600 dark:text-yellow-400" @click="open(c)">Cancel Rejection</button>
            </td>
          </tr>
          <tr v-if="!rejectedOvertimes.length">
            <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">No rejected Overtimes 🎉</td>
          </tr>
        </tbody>
      </table>
    </div>

    
    <!-- Modal -->
    <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="close">
      <div class="w-full max-w-xl rounded-xl bg-white dark:bg-gray-800 shadow-xl">
        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
          <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Review Overtime</h2>
            <p class="text-xs text-gray-500">Overtime ID #{{ selected.id }}</p>
          </div>
          <span class="px-3 py-1 text-xs rounded-full font-medium" :class="{
            'bg-blue-100 text-blue-700': activeTab === 'pending',
            'bg-green-100 text-green-700': activeTab === 'approved',
            'bg-red-100 text-red-700': activeTab === 'rejected',
          }">{{ activeTab.toUpperCase() }}</span>
        </div>

        <div class="px-6 py-5 space-y-4 text-sm">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-gray-500">Employee</p>
              <p class="font-medium">{{ selected.creator?.name || selected.empnum }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Date</p>
              <p class="font-medium">{{ formatISOToDate(selected.timekeeping?.dated) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Start Time</p>
              <p class="font-medium">{{ formatISOToCustom(selected.startTime) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">End Time</p>
              <p class="font-medium">{{ formatISOToCustom(selected.endTime) }}</p>
            </div>
            <div class="col-span-2">
              <p class="text-xs text-gray-500">Overtime Reason</p>
              <p class="font-medium">{{ selected.reasons || '—' }}</p>
            </div>
          </div>

          <!-- Error message from ValidationException -->
          <div v-if="errorMessage" class="p-2 text-sm text-red-700 bg-red-100 rounded">
            {{ errorMessage }}
          </div>

          <!-- Reject reason -->
          <div v-if="activeTab === 'pending'">
            <label class="block text-xs font-medium mb-1 text-gray-600 dark:text-gray-400">Reject Reason</label>
            <textarea v-model="rejectReason" placeholder="Provide reason if rejecting…" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600" />
          </div>
        </div>

        <div class="flex justify-between items-center px-6 py-4 border-t dark:border-gray-700">
          <button class="text-sm text-gray-600 dark:text-gray-300 hover:underline" @click="close">Cancel</button>
          <div class="flex gap-2">
            <button v-if="activeTab === 'pending'" class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700" :disabled="!rejectReason.trim()" @click="reject(selected.id)">Reject</button>
            <button v-if="activeTab === 'pending'" class="px-4 py-2 text-sm rounded-lg bg-green-600 text-white hover:bg-green-700" @click="approve(selected.id)">Approve</button>
            <button v-if="activeTab === 'approved'" class="px-4 py-2 text-sm rounded-lg bg-yellow-600 text-white hover:bg-yellow-700" @click="cancelApproval(selected.id)">Cancel Approval</button>
            <button v-if="activeTab === 'rejected'" class="px-4 py-2 text-sm rounded-lg bg-yellow-600 text-white hover:bg-yellow-700" @click="cancelRejection(selected.id)">Cancel Rejection</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</AppLayout>
</template>
