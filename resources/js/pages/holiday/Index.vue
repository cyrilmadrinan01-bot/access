<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { route } from "ziggy-js";

interface Holiday {
  id: number;
  name: string;
  date: string;
  type: "Legal" | "Special";
  day_type_code: string;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Pagination<T> {
  data: T[];
  links: PaginationLink[];
}

const props = defineProps<{
  holidays: Pagination<Holiday>;
  filters: {
    search: string;
    sort: string;
    direction: string;
  };
}>();

const search = ref(props.filters.search ?? "");
const sort = ref(props.filters.sort ?? "date");
const direction = ref(props.filters.direction ?? "asc");

const showModal = ref(false);
const showImportModal = ref(false);
const importFile = ref<File | null>(null);

const form = ref({
  name: "",
  date: "",
  type: "Legal",
  day_type_code: "",
});

const resetForm = () => {
  form.value = {
    name: "",
    date: "",
    type: "Legal",
    day_type_code: "",
  };
};

const submit = () => {
  router.post("/admin/holidays/store", form.value, {
    onSuccess: () => {
      showModal.value = false;
      resetForm();
    },
  });
};

const applyFilters = () => {
  router.get(
    route("holidays.index"),
    {
      search: search.value,
      sort: sort.value,
      direction: direction.value,
    },
    {
      preserveState: true,
      replace: true,
    }
  );
};

const sortBy = (field: string) => {
  if (sort.value === field) {
    direction.value = direction.value === "asc" ? "desc" : "asc";
  } else {
    sort.value = field;
    direction.value = "asc";
  }

  applyFilters();
};

const importHolidays = () => {
  if (!importFile.value) return;

  const formData = new FormData();
  formData.append("file", importFile.value);

  router.post("/admin/holidays/import", formData, {
    forceFormData: true,
    onSuccess: () => {
      showImportModal.value = false;
      importFile.value = null;
    },
  });
};

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
  <AppLayout>
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <!-- Search -->
        <div class="flex items-center gap-2">
          <input
            v-model="search"
            @keyup.enter="applyFilters"
            type="text"
            placeholder="Search holiday..."
            class="px-3 py-2 border rounded-lg text-sm w-64 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
          />

          <button
            @click="applyFilters"
            class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900 dark:bg-gray-600 dark:hover:bg-gray-500"
          >
            Search
          </button>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <button
            @click="showImportModal = true"
            class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm"
          >
            ⬆ Import Holidays
          </button>

          <button
            @click="showModal = true"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm"
          >
            + Add Holiday
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-xl shadow bg-white dark:bg-gray-800">
        <table class="min-w-full text-sm text-left">
          <thead
            class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase text-xs"
          >
            <tr>
              <th @click="sortBy('name')" class="px-4 py-3 cursor-pointer">
                Holiday Name
              </th>

              <th @click="sortBy('date')" class="px-4 py-3 cursor-pointer">Date</th>

              <th @click="sortBy('type')" class="px-4 py-3 cursor-pointer">Type</th>

              <th @click="sortBy('day_type_code')" class="px-4 py-3 cursor-pointer">
                Day Type Code
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="holiday in holidays.data"
              :key="holiday.id"
              class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                {{ holiday.name }}
              </td>

              <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                {{ formatISOToDate(holiday.date) }}
              </td>

              <td class="px-4 py-3">
                <span
                  :class="
                    holiday.type === 'Legal'
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                      : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'
                  "
                  class="px-2 py-1 rounded-full text-xs font-semibold"
                >
                  {{ holiday.type }}
                </span>
              </td>

              <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                {{ holiday.day_type_code }}
              </td>
            </tr>

            <tr v-if="holidays.data.length === 0">
              <td
                colspan="4"
                class="px-4 py-6 text-center text-gray-400 dark:text-gray-500"
              >
                No holidays found.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          class="flex justify-center mt-4 space-x-1 px-2 py-3 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-b-xl"
        >
          <template v-for="link in holidays.links" :key="link.label">
            <button
              v-if="link.url"
              @click="router.visit(link.url)"
              v-html="link.label"
              class="px-3 py-1 text-sm rounded-md border dark:border-gray-600"
              :class="{
                'bg-blue-600 text-white': link.active,
                'bg-white dark:bg-gray-700 dark:text-white': !link.active,
              }"
            />
          </template>
        </div>
      </div>
    </div>

    <!-- ADD HOLIDAY MODAL -->
    <transition name="fade">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
      >
        <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-2xl">
          <!-- Header -->
          <div
            class="flex justify-between items-center px-6 py-4 border-b dark:border-gray-700"
          >
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
              Add Holiday
            </h2>

            <button
              @click="showModal = false"
              class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl"
            >
              ✕
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 grid md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label
                class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                Holiday Name
              </label>

              <input
                v-model="form.name"
                type="text"
                placeholder="Holiday Name"
                class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
              />
            </div>

            <div>
              <label
                class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                Date
              </label>

              <input
                v-model="form.date"
                type="date"
                class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
              />
            </div>

            <div>
              <label
                class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                Type
              </label>

              <select
                v-model="form.type"
                class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
              >
                <option value="Legal">Legal</option>
                <option value="Special">Special</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label
                class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                Day Type Code
              </label>

              <input
                v-model="form.day_type_code"
                type="text"
                placeholder="REGHOL / SPHOL"
                class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
              />
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t dark:border-gray-700 flex justify-end gap-2">
            <button
              @click="showModal = false"
              class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg"
            >
              Cancel
            </button>

            <button @click="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">
              Save Holiday
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- IMPORT MODAL -->
    <transition name="fade">
      <div
        v-if="showImportModal"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
      >
        <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl shadow-2xl">
          <!-- Header -->
          <div
            class="flex justify-between items-center px-6 py-4 border-b dark:border-gray-700"
          >
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
              Import Holidays
            </h2>

            <button
              @click="showImportModal = false"
              class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl"
            >
              ✕
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 space-y-4">
            <div>
              <label class="font-medium text-sm text-gray-700 dark:text-gray-300">
                Upload Excel File
              </label>

              <input
                type="file"
                accept=".xlsx,.xls"
                @change="(e: any) => importFile = e.target.files[0]"
                class="mt-2 w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600"
              />
            </div>

            <p class="text-xs text-gray-500">
              Make sure your file matches the holiday import template.
            </p>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t dark:border-gray-700 flex justify-end gap-2">
            <a
              href="/templates/holidays_import_template.xlsx"
              class="text-sm text-blue-600 hover:underline"
            >
              Download Import Template
            </a>

            <button
              @click="showImportModal = false"
              class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg"
            >
              Cancel
            </button>

            <button
              @click="importHolidays"
              class="px-4 py-2 bg-emerald-600 text-white rounded-lg"
            >
              Import
            </button>
          </div>
        </div>
      </div>
    </transition>
  </AppLayout>
</template>
