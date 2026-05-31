<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import { route } from 'ziggy-js'
import { ref } from 'vue'

defineProps<{
    shiftcodes: any[]
}>()

const days = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
]

const selectedWorkDays = ref<string[]>([])
const selectedRestDays = ref<string[]>([])

const showModal = ref(false)
const editing = ref(false)

const form = useForm({
    id: null as number | null,

    shiftCode: '',
    shiftStart: '',
    shiftEnd: '',
    hoursWorked: '',
    regHours: '',

    withNd: 'No',
    ndStart: '',
    ndEnd: '',
    ndHours: '',

    workDay: '',
    restDay: '',

    usShift: 'No',
    sameDay: 'Yes',

    ndCrossDayStart: '',
    ndCrossDayEnd: '',

    rotatingShift: 'No',
    group: '',

    is_active: true,
})


function openCreate() {
    editing.value = false
    form.reset()

    selectedWorkDays.value = []
    selectedRestDays.value = []

    showModal.value = true
}

function openEdit(s: any) {
    editing.value = true

    Object.assign(form, {
        id: s.id,
        shiftCode: s.shiftCode,
        shiftStart: s.shiftStart,
        shiftEnd: s.shiftEnd,
        hoursWorked: s.hoursWorked,
        regHours: s.regHours,

        withNd: s.withNd,
        ndStart: s.ndStart,
        ndEnd: s.ndEnd,
        ndHours: s.ndHours,

        workDay: s.workDay,
        restDay: s.restDay,

        usShift: s.usShift,
        sameDay: s.sameDay,

        ndCrossDayStart: s.ndCrossDayStart,
        ndCrossDayEnd: s.ndCrossDayEnd,

        rotatingShift: s.rotatingShift,
        group: s.group,

        is_active: s.is_active,
    })

    selectedWorkDays.value = s.workDay
        ? s.workDay.split('|')
        : []

    selectedRestDays.value = s.restDay
        ? s.restDay.split('|')
        : []

    showModal.value = true
}


function submit() {
    form.workDay = selectedWorkDays.value.join('|')
    form.restDay = selectedRestDays.value.join('|')

    if (editing.value) {
        form.put(`/admin/shiftcodes/${form.id}`, {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            },
        })
    } else {
        form.post(`/admin/shiftcodes`, {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            },
        })
    }
}


function toggle(id: number) {
    if (!confirm('Toggle shiftcode status?')) return
    form.patch(`/admin/shiftcodes/${id}/toggle`)
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shiftcode Management',
        href: route('admin.shiftcode'),
    },
]
</script>

<template>
  <Head title="Shiftcode Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
          Shiftcode Management
        </h1>

        <button
          @click="openCreate"
          class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400"
        >
          + Add Shiftcode
        </button>
      </div>

      <!-- Table -->
      <div
        class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
      >
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left">Code</th>
              <th class="px-4 py-3 text-left">Start</th>
              <th class="px-4 py-3 text-left">End</th>
              <th class="px-4 py-3 text-left">Hours</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="s in shiftcodes"
              :key="s.id"
              class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
            >
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                {{ s.shiftCode }}
              </td>
              <td class="px-4 py-3">{{ s.shiftStart }}</td>
              <td class="px-4 py-3">{{ s.shiftEnd }}</td>
              <td class="px-4 py-3">{{ s.hoursWorked }}</td>

              <!-- Status badge -->
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                  :class="
                    s.is_active
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                      : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                  "
                >
                  {{ s.is_active ? "Active" : "Disabled" }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3 text-right space-x-2">
                <button
                  @click="openEdit(s)"
                  class="rounded-md px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700"
                >
                  Edit
                </button>

                <button
                  @click="toggle(s.id)"
                  class="rounded-md px-3 py-1.5 text-sm bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50"
                >
                  {{ s.is_active ? "Disable" : "Enable" }}
                </button>
              </td>
            </tr>

            <tr v-if="shiftcodes.length === 0">
              <td
                colspan="6"
                class="px-4 py-6 text-center text-gray-500 dark:text-gray-400"
              >
                No shiftcodes found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal -->
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm overflow-y-auto px-4"
      >
        <div
          class="w-full max-w-5xl rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xl"
        >
          <!-- Header -->
          <div class="px-6 py-4 border-b dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
              {{ editing ? "Edit Shiftcode" : "Create Shiftcode" }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Configure shift schedule, ND rules, and work flags
            </p>
          </div>

          <!-- Body -->
          <div class="p-6 space-y-8 max-h-[75vh] overflow-y-auto">
            <!-- BASIC SHIFT INFO -->
            <section>
              <h3
                class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400"
              >
                Basic Shift Information
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Shift Code
                  </label>
                  <input
                    v-model="form.shiftCode"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Group
                  </label>
                  <input
                    v-model="form.group"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Hours Worked
                  </label>
                  <input
                    type="number"
                    step="0.25"
                    v-model="form.hoursWorked"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Shift Start
                  </label>
                  <input
                    type="time"
                    v-model="form.shiftStart"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Shift End
                  </label>
                  <input
                    type="time"
                    v-model="form.shiftEnd"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Regular Hours
                  </label>
                  <input
                    type="number"
                    step="0.25"
                    v-model="form.regHours"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>
              </div>
            </section>

            <!-- WORK / DAY RULES -->
            <section>
              <h3
                class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400"
              >
                Work & Day Rules
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Work Day(s)
                  </label>
                  <div class="space-y-2">
                    <label v-for="day in days" :key="day" class="flex items-center gap-2">
                      <input
                        type="checkbox"
                        :value="day"
                        v-model="selectedWorkDays"
                        class="rounded border-gray-300"
                      />
                      <span>{{ day }}</span>
                    </label>
                  </div>
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Rest Day(s)
                  </label>
                  <div class="space-y-2">
                    <label
                      v-for="day in days"
                      :key="`rest-${day}`"
                      class="flex items-center gap-2"
                    >
                      <input
                        type="checkbox"
                        :value="day"
                        v-model="selectedRestDays"
                        class="rounded border-gray-300"
                      />
                      <span>{{ day }}</span>
                    </label>
                  </div>
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Same Day
                  </label>
                  <select
                    v-model="form.sameDay"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                  </select>
                </div>
              </div>
            </section>

            <!-- NIGHT DIFFERENTIAL -->
            <section>
              <h3
                class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400"
              >
                Night Differential (ND)
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    With Night Differential
                  </label>
                  <select
                    v-model="form.withNd"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                  </select>
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Night Differential Start
                  </label>
                  <input
                    type="time"
                    v-model="form.ndStart"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Night Differential End
                  </label>
                  <input
                    type="time"
                    v-model="form.ndEnd"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Night Differential Hours
                  </label>
                  <input
                    type="number"
                    step="0.25"
                    v-model="form.ndHours"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Cross Day Start
                  </label>
                  <input
                    type="time"
                    v-model="form.ndCrossDayStart"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >Cross Day End</label
                  >
                  <input
                    type="time"
                    v-model="form.ndCrossDayEnd"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>
              </div>
            </section>

            <!-- ADVANCED FLAGS -->
            <section>
              <h3
                class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400"
              >
                Advanced Flags
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    US Shift
                  </label>
                  <select
                    v-model="form.usShift"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                  </select>
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                  >
                    Rotating Shift
                  </label>
                  <select
                    v-model="form.rotatingShift"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                  </select>
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >Status</label
                  >
                  <select
                    v-model="form.is_active"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option :value="true">Active</option>
                    <option :value="false">Disabled</option>
                  </select>
                </div>
              </div>
            </section>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t dark:border-gray-700">
            <button
              @click="showModal = false"
              class="rounded-lg px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700"
            >
              Cancel
            </button>

            <button
              @click="submit"
              class="rounded-lg px-5 py-2 text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400"
            >
              Save Shiftcode
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
