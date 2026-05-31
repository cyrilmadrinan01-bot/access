<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
  },
]

const props = withDefaults(
  defineProps<{
    filedCorrections?: any[]
    overtimes?: any[]
    timeoffs?: any[]

    approvalCorrections?: any[]
    approvalOvertimes?: any[]
    approvalTimeoffs?: any[]

    cutoff?: any
  }>(),
  {
    filedCorrections: () => [],
    overtimes: () => [],
    timeoffs: () => [],

    approvalCorrections: () => [],
    approvalOvertimes: () => [],
    approvalTimeoffs: () => [],

    cutoff: () => null,
  }
)

const totalRequests = computed(() => {
  return (
    props.filedCorrections.length +
    props.overtimes.length +
    props.timeoffs.length
  )
})

const totalPendingApprovals = computed(() => {
  return (
    props.approvalCorrections.length +
    props.approvalOvertimes.length +
    props.approvalTimeoffs.length
  )
})

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Approved':
      return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'

    case 'Rejected':
      return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'

    default:
      return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300'
  }
}

const formatDate = (date: string) => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4">
      <!-- ======================================================
           HEADER
      ======================================================= -->

      <section
        class="rounded-2xl border bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
      >
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              Employee Dashboard
            </h1>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Manage requests, approvals, and payroll activities
            </p>
          </div>

          <div class="rounded-xl bg-gray-100 px-4 py-3 dark:bg-gray-800">
            <div class="text-xs uppercase tracking-wide text-gray-500">
              Current Payroll Cutoff
            </div>

            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
              {{
                cutoff
                  ? `${formatDate(cutoff.cutOffStart)} - ${formatDate(cutoff.cutOffEnd)}`
                  : "No Active Cutoff"
              }}
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================
           SUMMARY CARDS
      ======================================================= -->

      <section>
        <div class="grid gap-4 md:grid-cols-4">
          <div
            class="rounded-2xl border bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Requests</div>

            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
              {{ totalRequests }}
            </div>
          </div>

          <div
            class="rounded-2xl border bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="text-sm text-gray-500 dark:text-gray-400">Pending Approvals</div>

            <div class="mt-2 text-3xl font-bold text-yellow-600">
              {{ totalPendingApprovals }}
            </div>
          </div>

          <div
            class="rounded-2xl border bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="text-sm text-gray-500 dark:text-gray-400">Corrections</div>

            <div class="mt-2 text-3xl font-bold text-blue-600">
              {{ filedCorrections.length }}
            </div>
          </div>

          <div
            class="rounded-2xl border bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="text-sm text-gray-500 dark:text-gray-400">Overtimes</div>

            <div class="mt-2 text-3xl font-bold text-purple-600">
              {{ overtimes.length }}
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================
           MY REQUESTS
      ======================================================= -->

      <section>
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-bold text-gray-900 dark:text-white">My Requests</h2>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
          <!-- ======================================================
               TIMEKEEPING CORRECTIONS
          ======================================================= -->

          <div
            class="rounded-2xl border bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="border-b p-4 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">
                    Timekeeping Corrections
                  </h3>

                  <p class="text-xs text-gray-500">My correction requests</p>
                </div>

                <div
                  class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                >
                  {{ filedCorrections.length }}
                </div>
              </div>
            </div>

            <div class="max-h-[500px] space-y-3 overflow-y-auto p-4">
              <div
                v-for="item in filedCorrections"
                :key="item.id"
                class="rounded-xl border p-4 dark:border-gray-700"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                      Correction Request #{{ item.id }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                      Filed:
                      {{ formatDate(item.created_at) }}
                    </div>
                  </div>

                  <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="getStatusClass(item.status)"
                  >
                    {{ item.status }}
                  </span>
                </div>

                <div
                  v-if="item.rejected_reason"
                  class="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-700 dark:bg-red-900/20 dark:text-red-300"
                >
                  {{ item.rejected_reason }}
                </div>
              </div>

              <div
                v-if="filedCorrections.length === 0"
                class="py-10 text-center text-sm text-gray-400"
              >
                No requests found
              </div>
            </div>
          </div>

          <!-- ======================================================
               OVERTIME
          ======================================================= -->

          <div
            class="rounded-2xl border bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="border-b p-4 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">
                    Overtime Requests
                  </h3>

                  <p class="text-xs text-gray-500">My overtime applications</p>
                </div>

                <div
                  class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300"
                >
                  {{ overtimes.length }}
                </div>
              </div>
            </div>

            <div class="max-h-[500px] space-y-3 overflow-y-auto p-4">
              <div
                v-for="item in overtimes"
                :key="item.id"
                class="rounded-xl border p-4 dark:border-gray-700"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                      Overtime Request #{{ item.id }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                      Filed:
                      {{ formatDate(item.created_at) }}
                    </div>
                  </div>

                  <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="getStatusClass(item.status)"
                  >
                    {{ item.status }}
                  </span>
                </div>

                <div
                  v-if="item.rejected_reason"
                  class="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-700 dark:bg-red-900/20 dark:text-red-300"
                >
                  {{ item.rejected_reason }}
                </div>
              </div>

              <div
                v-if="overtimes.length === 0"
                class="py-10 text-center text-sm text-gray-400"
              >
                No overtime requests
              </div>
            </div>
          </div>

          <!-- ======================================================
               TIME OFF
          ======================================================= -->

          <div
            class="rounded-2xl border bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="border-b p-4 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">
                    Leave Requests
                  </h3>

                  <p class="text-xs text-gray-500">My leave applications</p>
                </div>

                <div
                  class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300"
                >
                  {{ timeoffs.length }}
                </div>
              </div>
            </div>

            <div class="max-h-[500px] space-y-3 overflow-y-auto p-4">
              <div
                v-for="item in timeoffs"
                :key="item.id"
                class="rounded-xl border p-4 dark:border-gray-700"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                      Leave Request #{{ item.id }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                      Filed:
                      {{ formatDate(item.created_at) }}
                    </div>
                  </div>

                  <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="getStatusClass(item.status)"
                  >
                    {{ item.status }}
                  </span>
                </div>

                <div
                  v-if="item.rejected_reason"
                  class="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-700 dark:bg-red-900/20 dark:text-red-300"
                >
                  {{ item.rejected_reason }}
                </div>
              </div>

              <div
                v-if="timeoffs.length === 0"
                class="py-10 text-center text-sm text-gray-400"
              >
                No leave requests
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================
           PENDING APPROVALS
      ======================================================= -->

      <section>
        <div class="mb-4">
          <h2 class="text-lg font-bold text-gray-900 dark:text-white">
            Pending Approvals
          </h2>

          <p class="text-sm text-gray-500">Requests waiting for your action</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
          <!-- Corrections -->

          <div
            class="rounded-2xl border bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="border-b p-4 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Corrections</h3>

                <div
                  class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                >
                  {{ approvalCorrections.length }}
                </div>
              </div>
            </div>

            <div class="max-h-[400px] space-y-3 overflow-y-auto p-4">
              <div
                v-for="item in approvalCorrections"
                :key="item.id"
                class="rounded-xl border p-3 dark:border-gray-700"
              >
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ item.creator?.employee?.name }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                  {{ formatDate(item.created_at) }}
                </div>
              </div>

              <div
                v-if="approvalCorrections.length === 0"
                class="py-8 text-center text-sm text-gray-400"
              >
                No pending approvals
              </div>
            </div>
          </div>

          <!-- Overtime -->

          <div
            class="rounded-2xl border bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="border-b p-4 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Overtime</h3>

                <div
                  class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300"
                >
                  {{ approvalOvertimes.length }}
                </div>
              </div>
            </div>

            <div class="max-h-[400px] space-y-3 overflow-y-auto p-4">
              <div
                v-for="item in approvalOvertimes"
                :key="item.id"
                class="rounded-xl border p-3 dark:border-gray-700"
              >
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ item.creator?.employee?.name }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                  {{ formatDate(item.created_at) }}
                </div>
              </div>

              <div
                v-if="approvalOvertimes.length === 0"
                class="py-8 text-center text-sm text-gray-400"
              >
                No pending approvals
              </div>
            </div>
          </div>

          <!-- Time Off -->

          <div
            class="rounded-2xl border bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="border-b p-4 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Time Off</h3>

                <div
                  class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300"
                >
                  {{ approvalTimeoffs.length }}
                </div>
              </div>
            </div>

            <div class="max-h-[400px] space-y-3 overflow-y-auto p-4">
              <div
                v-for="item in approvalTimeoffs"
                :key="item.id"
                class="rounded-xl border p-3 dark:border-gray-700"
              >
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ item.creator?.employee?.name }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                  {{ formatDate(item.created_at) }}
                </div>
              </div>

              <div
                v-if="approvalTimeoffs.length === 0"
                class="py-8 text-center text-sm text-gray-400"
              >
                No pending approvals
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
