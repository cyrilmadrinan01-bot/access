<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
  },
]

withDefaults(
  defineProps<{
    filedCorrections?: any[]
    overtimes?: any[]
    timeoffs?: any[]

    approvalCorrections?: any[]
    approvalOvertimes?: any[]
    approvalTimeoffs?: any[]
  }>(),
  {
    filedCorrections: () => [],
    overtimes: () => [],
    timeoffs: () => [],

    approvalCorrections: () => [],
    approvalOvertimes: () => [],
    approvalTimeoffs: () => [],
  }
)
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4">
      <!-- ======================================================
           ROW 1: OWNED REQUESTS
      ======================================================= -->
      <section>
        <h2 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">My Requests</h2>

        <div class="grid gap-4 md:grid-cols-3">
          <!-- Corrections -->
          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="mb-2 flex justify-between">
              <span class="font-semibold text-gray-800 dark:text-gray-100"
                >Filed Corrections</span
              >
              <span class="text-sm text-blue-600">{{ filedCorrections.length }}</span>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto">
              <div
                v-for="item in filedCorrections"
                :key="item.id"
                class="rounded border p-2 dark:border-gray-700"
              >
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ item.employee_name }}
                </div>
                <div class="text-xs text-gray-500">{{ item.date }}</div>
                <span
                  class="mt-1 inline-block rounded px-2 py-1 text-xs"
                  :class="
                    item.status === 'Approved'
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                      : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'
                  "
                >
                  {{ item.status }}
                </span>
              </div>
            </div>
          </div>

          <!-- Overtime -->
          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="mb-2 flex justify-between">
              <span class="font-semibold text-gray-800 dark:text-gray-100">Overtime</span>
              <span class="text-sm text-purple-600">{{ overtimes.length }}</span>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto">
              <div
                v-for="item in overtimes"
                :key="item.id"
                class="rounded border p-2 dark:border-gray-700"
              >
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ item.employee_name }}
                </div>
                <div class="text-xs text-gray-500">{{ item.date }}</div>
                <span
                  class="mt-1 inline-block rounded px-2 py-1 text-xs"
                  :class="
                    item.status === 'Approved'
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                      : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'
                  "
                >
                  {{ item.status }}
                </span>
              </div>
            </div>
          </div>

          <!-- Time Off -->
          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="mb-2 flex justify-between">
              <span class="font-semibold text-gray-800 dark:text-gray-100">Time Off</span>
              <span class="text-sm text-red-600">{{ timeoffs.length }}</span>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto">
              <div
                v-for="item in timeoffs"
                :key="item.id"
                class="rounded border p-2 dark:border-gray-700"
              >
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ item.employee_name }}
                </div>
                <div class="text-xs text-gray-500">{{ item.date }}</div>
                <span
                  class="mt-1 inline-block rounded px-2 py-1 text-xs"
                  :class="
                    item.status === 'Approved'
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                      : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'
                  "
                >
                  {{ item.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================
           ROW 2: APPROVER PENDING
      ======================================================= -->
      <section>
        <h2 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">
          Pending Approvals
        </h2>

        <div class="grid gap-4 md:grid-cols-3">
          <!-- Corrections -->
          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="mb-2 flex justify-between">
              <span class="font-semibold">Corrections</span>
              <span class="text-sm text-blue-600">{{ approvalCorrections.length }}</span>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto">
              <div
                v-for="item in approvalCorrections"
                :key="item.id"
                class="rounded border p-2 dark:border-gray-700"
              >
                <div class="text-sm font-medium">{{ item.employee_name }}</div>
                <div class="text-xs text-gray-500">{{ item.date }}</div>
              </div>

              <div v-if="approvalCorrections.length === 0" class="text-sm text-gray-400">
                No pending approvals
              </div>
            </div>
          </div>

          <!-- Overtime -->
          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="mb-2 flex justify-between">
              <span class="font-semibold">Overtime</span>
              <span class="text-sm text-purple-600">{{ approvalOvertimes.length }}</span>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto">
              <div
                v-for="item in approvalOvertimes"
                :key="item.id"
                class="rounded border p-2 dark:border-gray-700"
              >
                <div class="text-sm font-medium">{{ item.employee_name }}</div>
                <div class="text-xs text-gray-500">{{ item.date }}</div>
              </div>

              <div v-if="approvalOvertimes.length === 0" class="text-sm text-gray-400">
                No pending approvals
              </div>
            </div>
          </div>

          <!-- Time Off -->
          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="mb-2 flex justify-between">
              <span class="font-semibold">Time Off</span>
              <span class="text-sm text-red-600">{{ approvalTimeoffs.length }}</span>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto">
              <div
                v-for="item in approvalTimeoffs"
                :key="item.id"
                class="rounded border p-2 dark:border-gray-700"
              >
                <div class="text-sm font-medium">{{ item.employee_name }}</div>
                <div class="text-xs text-gray-500">{{ item.date }}</div>
              </div>

              <div v-if="approvalTimeoffs.length === 0" class="text-sm text-gray-400">
                No pending approvals
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================
           ROW 3: SUMMARY
      ======================================================= -->
      <section>
        <h2 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">Summary</h2>

        <div class="grid gap-4 md:grid-cols-4">
          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="text-sm text-gray-500">My Requests</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ filedCorrections.length + overtimes.length + timeoffs.length }}
            </div>
          </div>

          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="text-sm text-gray-500">Pending Approvals</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{
                approvalCorrections.length +
                approvalOvertimes.length +
                approvalTimeoffs.length
              }}
            </div>
          </div>

          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="text-sm text-gray-500">Corrections</div>
            <div class="text-2xl font-bold">{{ filedCorrections.length }}</div>
          </div>

          <div
            class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="text-sm text-gray-500">Overtime</div>
            <div class="text-2xl font-bold">{{ overtimes.length }}</div>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
