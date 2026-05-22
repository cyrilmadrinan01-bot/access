<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import type { BreadcrumbItem } from '@/types'

interface Leave {
    id: number
    start_date: string
    end_date: string
    days: number
    hours: number
    reason: string | null
    status: 'Pending' | 'Approved' | 'Rejected'
    user: {
        name: string
    }
    leave_type: {
        id: number
        name: string
        code: string
    }
}

const props = defineProps<{
    leaves: Leave[]
}>()

const activeTab = ref<'pending' | 'approved' | 'rejected'>('pending')
const selectedLeave = ref<Leave | null>(null)
const remarks = ref('')
const errorMessage = ref('')

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Approval', href: '' },
    { title: 'Leave Requests', href: '' },
]

// Separate leaves by status for tabs
const pendingLeaves = computed(() => props.leaves.filter(l => l.status === 'Pending'))
const approvedLeaves = computed(() => props.leaves.filter(l => l.status === 'Approved'))
const rejectedLeaves = computed(() => props.leaves.filter(l => l.status === 'Rejected'))

function openModal(leave: Leave) {
    selectedLeave.value = leave
    remarks.value = ''
    errorMessage.value = ''
}

function closeModal() {
    selectedLeave.value = null
    remarks.value = ''
    errorMessage.value = ''
}

function approve() {
    if (!selectedLeave.value) return

    router.post(
        `/approvals/leave-approvals/${selectedLeave.value.id}/approve`,
        { remarks: remarks.value },
        { onSuccess: closeModal }
    )
}

function reject() {
    if (!selectedLeave.value) return
    if (!remarks.value.trim()) {
        errorMessage.value = 'Remarks are required for rejection.'
        return
    }

    router.post(
        `/approvals/leave-approvals/${selectedLeave.value.id}/reject`,
        { remarks: remarks.value },
        { onSuccess: closeModal }
    )
}

function formatISOToDate(dateTime: string | null | undefined) {
    if (!dateTime) return '—'
    const clean = dateTime.split('.')[0].replace('T', ' ')
    const [date, time] = clean.split(' ')
    if (!date || !time) return dateTime
    const [year, month, day] = date.split('-')
    return `${month}-${day}-${year}`
}

// Cancel Approved leave
function cancelApproval(leave: Leave) {
    router.post(`/approvals/leave-approvals/${leave.id}/cancel-approval`, {}, {
        onSuccess: closeModal,
        onError: (errors) => { if (errors.leave) errorMessage.value = errors.leave }
    })
}

// Cancel Rejected leave
function cancelRejection(leave: Leave) {
    router.post(`/approvals/leave-approvals/${leave.id}/cancel-rejection`, {}, {
        onSuccess: closeModal,
        onError: (errors) => { if (errors.leave) errorMessage.value = errors.leave }
    })
}

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">
                Leave Approvals
            </h1>

            <!-- TABS -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
                <button class="px-4 py-2 font-medium"
                    :class="activeTab === 'pending' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 dark:text-gray-400'"
                    @click="activeTab = 'pending'">
                    Pending
                </button>
                <button class="px-4 py-2 font-medium"
                    :class="activeTab === 'approved' ? 'border-b-2 border-green-600 text-green-600' : 'text-gray-500 dark:text-gray-400'"
                    @click="activeTab = 'approved'">
                    Approved
                </button>
                <button class="px-4 py-2 font-medium"
                    :class="activeTab === 'rejected' ? 'border-b-2 border-red-600 text-red-600' : 'text-gray-500 dark:text-gray-400'"
                    @click="activeTab = 'rejected'">
                    Rejected
                </button>
            </div>

            <!-- TABLES PER TAB -->
            <div v-if="activeTab === 'pending'" class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="p-3 text-left">Employee</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Period</th>
                            <th class="p-3 text-left">Reason</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="leave in pendingLeaves" :key="leave.id" class="border-t dark:border-gray-700">
                            <td class="p-3">{{ leave.user.name }}</td>
                            <td class="p-3">{{ leave.leave_type.name }}</td>
                            <td class="p-3">{{ formatISOToDate(leave.start_date) }} - {{ formatISOToDate(leave.end_date)
                                }}</td>
                            <td class="p-3">{{ leave.reason || '—' }}</td>
                            <td class="p-3 text-center">
                                <button class="text-blue-600 dark:text-blue-400 hover:underline"
                                    @click="openModal(leave)">Review</button>
                            </td>
                        </tr>
                        <tr v-if="!pendingLeaves.length">
                            <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                No pending leave requests 🎉
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="activeTab === 'approved'" class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="p-3 text-left">Employee</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Period</th>
                            <th class="p-3 text-left">Reason</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="leave in approvedLeaves" :key="leave.id" class="border-t dark:border-gray-700">
                            <td class="p-3">{{ leave.user.name }}</td>
                            <td class="p-3">{{ leave.leave_type.name }}</td>
                            <td class="p-3">{{ formatISOToDate(leave.start_date) }} - {{ formatISOToDate(leave.end_date)
                                }}</td>
                            <td class="p-3">{{ leave.reason || '—' }}</td>
                            <td class="p-3 text-center">
                                <button class="text-green-600 dark:text-green-400 hover:underline"
                                    @click="openModal(leave)">Cancel Approval</button>
                            </td>
                        </tr>
                        <tr v-if="!approvedLeaves.length">
                            <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                No approved leaves 🎉
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="activeTab === 'rejected'" class="bg-white dark:bg-gray-800 rounded shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="p-3 text-left">Employee</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Period</th>
                            <th class="p-3 text-left">Reason</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="leave in rejectedLeaves" :key="leave.id" class="border-t dark:border-gray-700">
                            <td class="p-3">{{ leave.user.name }}</td>
                            <td class="p-3">{{ leave.leave_type.name }}</td>
                            <td class="p-3">{{ formatISOToDate(leave.start_date) }} - {{ formatISOToDate(leave.end_date)
                                }}</td>
                            <td class="p-3">{{ leave.reason || '—' }}</td>
                            <td class="p-3 text-center">
                                <button class="text-red-600 dark:text-red-400 hover:underline"
                                    @click="openModal(leave)">Cancel Rejection</button>
                            </td>
                        </tr>
                        <tr v-if="!rejectedLeaves.length">
                            <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                No rejected leaves 🎉
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- MODAL -->
            <div v-if="selectedLeave" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                @click.self="closeModal">
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-gray-800 shadow-xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Review Leave Request</h2>
                            <p class="text-xs text-gray-500">Leave ID #{{ selectedLeave.id }}</p>
                        </div>
                        <span class="px-3 py-1 text-xs rounded-full font-medium" :class="{
                            'bg-blue-100 text-blue-700': selectedLeave.status === 'Pending',
                            'bg-green-100 text-green-700': selectedLeave.status === 'Approved',
                            'bg-red-100 text-red-700': selectedLeave.status === 'Rejected',
                        }">{{ selectedLeave.status.toUpperCase() }}</span>
                    </div>

                    <div class="px-6 py-5 space-y-4 text-sm text-gray-700 dark:text-gray-300">
                        <div>
                            <p class="text-xs text-gray-500">Employee</p>
                            <p class="font-medium">{{ selectedLeave.user.name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Type</p>
                                <p class="font-medium">{{ selectedLeave.leave_type.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Period</p>
                                <p class="font-medium">{{ formatISOToDate(selectedLeave.start_date) }} - {{
                                    formatISOToDate(selectedLeave.end_date) }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Reason</p>
                            <p class="font-medium">{{ selectedLeave.reason || '—' }}</p>
                        </div>

                        <div v-if="selectedLeave.status === 'Pending'">
                            <label class="block text-xs font-medium mb-1 text-gray-600 dark:text-gray-400">
                                Remarks (required for rejection)
                            </label>
                            <textarea v-model="remarks" rows="3"
                                class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" />
                        </div>

                        <div v-if="errorMessage" class="p-2 text-sm text-red-700 bg-red-100 rounded">{{ errorMessage }}
                        </div>
                    </div>

                    <div class="flex justify-between items-center px-6 py-4 border-t dark:border-gray-700">
                        <button class="text-sm text-gray-600 dark:text-gray-300 hover:underline"
                            @click="closeModal">Cancel</button>
                        <div class="flex gap-2">
                            <button v-if="selectedLeave.status === 'Pending'"
                                class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700"
                                :disabled="!remarks.trim()" @click="reject">Reject</button>
                            <button v-if="selectedLeave.status === 'Pending'"
                                class="px-4 py-2 text-sm rounded-lg bg-green-600 text-white hover:bg-green-700"
                                @click="approve">Approve</button>

                            <button v-if="selectedLeave.status === 'Approved'"
                                class="px-4 py-2 text-sm rounded-lg bg-yellow-600 text-white hover:bg-yellow-700"
                                @click="cancelApproval(selectedLeave)">Cancel Approval</button>

                            <button v-if="selectedLeave.status === 'Rejected'"
                                class="px-4 py-2 text-sm rounded-lg bg-yellow-600 text-white hover:bg-yellow-700"
                                @click="cancelRejection(selectedLeave)">Cancel Rejection</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
