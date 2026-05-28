<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { route } from "ziggy-js";
import AppLayout from "@/layouts/AppLayout.vue";

/* ---------------------------------------------
| Types
--------------------------------------------- */
interface Employee {
  id: number;
  empnum: string;
  name: string;
  deptName: string;
  jobTitle: string;
  managerId: string;
  employeeStatus: string | null;
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

interface PicklistItem {
  id: number;
  code: string;
  label: string;
}

interface Picklists {
  company_code: PicklistItem[];
  department: PicklistItem[];
  employee_class: PicklistItem[];
  location: PicklistItem[];
  country: PicklistItem[];
  job_code: PicklistItem[];
  pay_grade: PicklistItem[];
  employee_status: PicklistItem[];
  marital_status: PicklistItem[];
  gender: PicklistItem[];
  nationality: PicklistItem[];
  employee_type: PicklistItem[];
}

interface ShiftCodeItem {
  id: number;
  shiftCode: string;
  shiftStart: string;
  shiftEnd: string;
}

const shiftCodes = ref<ShiftCodeItem[]>([]);

/* ---------------------------------------------
| Props
--------------------------------------------- */
const props = defineProps<{
  employees: Pagination<Employee>;
  filters: {
    search: string;
    sort: string;
    direction: string;
  };
}>();

/* ---------------------------------------------
| UI State
--------------------------------------------- */
const showModal = ref(false);
const showImportModal = ref(false);
const activeTab = ref("personal");

/* ---------------------------------------------
| Tabs
--------------------------------------------- */
const tabs = [
  { key: "personal", label: "Personal Info" },
  { key: "employment", label: "Employment" },
  { key: "contact", label: "Contact" },
  { key: "address", label: "Address" },
  { key: "compensation", label: "Compensation" },
];

/* ---------------------------------------------
| Filters
--------------------------------------------- */
const search = ref(props.filters.search ?? "");
const sort = ref(props.filters.sort ?? "name");
const direction = ref(props.filters.direction ?? "asc");

/* ---------------------------------------------
| Form State
--------------------------------------------- */
const form = ref({
  empnum: "",
  salutation: "",
  prefix: "",
  firstName: "",
  lastName: "",
  middleName: "",
  nickName: "",
  deptCode: "",
  jobTitle: "",
  managerId: "",
  manager_empnum: "",
  manager_name: "",

  salary: 0,
  payGrade: "",
  pay_type: "",
  factor: "",

  marital_status: "",
  nationality: "",
  gender: "",
  birth_date: "",
  effective_start: "",
  bank_name: "",
  account_number: "",
  religion: "",
  country_of_birth: "",

  company_code: "",
  department_code: "",
  job_code: "",

  status: "Active",
  hire_date: "",

  email: "",
  phone: "",
  mobile: "",
  contact_person: "",
  contact_person_number: "",

  address_line1: "",
  address_line2: "",
  city: "",
  province: "",
  postal_code: "",
  country: "",

  employee_class: "",
  employeeType: "",
  department_name: "",
  shiftCode: "",
  business_title: "",
  standard_hours: "",
});

/* ---------------------------------------------
| Picklists
--------------------------------------------- */
const picklists = ref<Picklists>({
  company_code: [],
  department: [],
  employee_class: [],
  location: [],
  country: [],
  job_code: [],
  pay_grade: [],
  employee_status: [],
  marital_status: [],
  gender: [],
  nationality: [],
  employee_type: [],
});

const loadPicklists = async () => {
  try {
    const { data } = await axios.get<Picklists>("/admin/picklists");

    picklists.value = {
      company_code: data.company_code ?? [],
      department: data.department ?? [],
      employee_class: data.employee_class ?? [],
      location: data.location ?? [],
      country: data.country ?? [],
      job_code: data.job_code ?? [],
      pay_grade: data.pay_grade ?? [],
      employee_status: data.employee_status ?? [],
      marital_status: data.marital_status ?? [],
      gender: data.gender ?? [],
      nationality: data.nationality ?? [],
      employee_type: data.employee_type ?? [],
    };
  } catch (err) {
    console.error("Picklist load failed", err);
  }
};

/* ---------------------------------------------
| Manager Lookup
--------------------------------------------- */
const managerLoading = ref(false);

const searchManager = async () => {
  const empnum = form.value.manager_empnum;

  if (!empnum || empnum.length < 3) {
    form.value.manager_name = "";
    return;
  }

  managerLoading.value = true;

  try {
    const { data } = await axios.get(route("employees.search"), {
      params: { empnum },
    });

    form.value.manager_name = data?.name ?? "";
  } catch (err) {
    console.error("Manager lookup failed", err);
    form.value.manager_name = "";
  } finally {
    managerLoading.value = false;
  }
};

watch(
  () => form.value.manager_empnum,
  () => searchManager()
);

/* ---------------------------------------------
| Shiftcode Lookup
--------------------------------------------- */
const loadShiftCodes = async () => {
  try {
    const { data } = await axios.get<ShiftCodeItem[]>("/admin/shift-codes");

    shiftCodes.value = data ?? [];
  } catch (err) {
    console.error("Shift codes load failed", err);
  }
};

onMounted(() => {
  loadPicklists();
  loadShiftCodes();
});

/* ---------------------------------------------
| Actions
--------------------------------------------- */
const applyFilters = () => {
  router.get(
    route("employees.index"),
    {
      search: search.value,
      sort: sort.value,
      direction: direction.value,
    },
    { preserveState: true, replace: true }
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

const goToProfile = (empnum: string) => {
  router.get(route("employees.profile", empnum));
};

const submit = () => {
  router.post("/admin/employees/modal-store", form.value, {
    onSuccess: () => {
      showModal.value = false;
    },
  });
};

/* ---------------------------------------------
| Import
--------------------------------------------- */
const importFile = ref<File | null>(null);

const importEmployees = () => {
  if (!importFile.value) return;

  const formData = new FormData();
  formData.append("file", importFile.value);

  router.post("/admin/employees/import", formData, {
    forceFormData: true,
    onSuccess: () => {
      showImportModal.value = false;
      importFile.value = null;
    },
  });
};

/* ---------------------------------------------
| Lifecycle
--------------------------------------------- */
onMounted(() => {
  loadPicklists();
});
</script>

<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <!-- Header: Search left, Add button right -->
      <div class="flex justify-between items-center">
        <!-- Left: Search -->
        <div class="flex items-center space-x-2">
          <input
            v-model="search"
            @keyup.enter="applyFilters"
            type="text"
            placeholder="Search employee..."
            class="px-3 py-2 border rounded-lg text-sm w-64 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
          />
          <button
            @click="applyFilters"
            class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900 dark:bg-gray-600 dark:hover:bg-gray-500"
          >
            Search
          </button>
        </div>

        <!-- Right: Actions -->
        <div class="flex gap-2">
          <button
            @click="showImportModal = true"
            class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm"
          >
            ⬆ Import Employees
          </button>

          <button
            @click="showModal = true"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm"
          >
            + Add New Employee
          </button>
        </div>
      </div>

      <!-- Table Container -->
      <div class="overflow-x-auto rounded-xl shadow bg-white dark:bg-gray-800">
        <table class="min-w-full text-sm text-left">
          <thead
            class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase text-xs"
          >
            <tr>
              <th @click="sortBy('empnum')" class="px-4 py-3 cursor-pointer">
                Employee No
              </th>
              <th @click="sortBy('name')" class="px-4 py-3 cursor-pointer">Name</th>
              <th @click="sortBy('deptName')" class="px-4 py-3 cursor-pointer">
                Department
              </th>
              <th @click="sortBy('jobTitle')" class="px-4 py-3 cursor-pointer">
                Job Title
              </th>
              <th class="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="emp in employees.data"
              :key="emp.id"
              @click="goToProfile(emp.empnum)"
              class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition"
            >
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ emp.empnum }}</td>
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                {{ emp.name }}
              </td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                {{ emp.deptName }}
              </td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                {{ emp.jobTitle }}
              </td>
              <td class="px-4 py-3">
                <span
                  :class="
                    emp.employeeStatus === 'Active'
                      ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                      : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                  "
                  class="px-2 py-1 rounded-full text-xs font-semibold"
                >
                  {{ emp.employeeStatus ?? "No Account" }}
                </span>
              </td>
            </tr>

            <tr v-if="employees.data.length === 0">
              <td
                colspan="5"
                class="px-4 py-6 text-center text-gray-400 dark:text-gray-500"
              >
                No employees found.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          class="flex justify-center mt-4 space-x-1 px-2 py-3 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-b-xl"
        >
          <template v-for="link in employees.links" :key="link.label">
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

          <!-- Add Employee Modal -->
          <transition name="fade">
            <div
              v-if="showModal"
              class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
            >
              <div
                class="bg-white dark:bg-gray-900 w-5/6 max-w-5xl max-h-[90vh] rounded-2xl shadow-2xl flex flex-col"
              >
                <!-- Header -->
                <div
                  class="flex justify-between items-center px-6 py-4 border-b dark:border-gray-700"
                >
                  <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Add New Employee
                  </h2>
                  <button
                    @click="showModal = false"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl"
                  >
                    ✕
                  </button>
                </div>

                <!-- Tabs -->
                <div
                  class="flex space-x-2 px-6 pt-4 border-b dark:border-gray-700 overflow-x-auto"
                >
                  <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg transition"
                    :class="
                      activeTab === tab.key
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                    "
                  >
                    {{ tab.label }}
                  </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                  <!-- PERSONAL TAB -->
                  <div v-if="activeTab === 'personal'" class="grid md:grid-cols-2 gap-4">
                    <div>
                      <label class="label-style">Salutation</label>
                      <input
                        v-model="form.salutation"
                        placeholder="Mr/Ms/Mrs.."
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Prefix</label>
                      <input
                        v-model="form.prefix"
                        placeholder="Sr/Jr/II.."
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">First Name</label>
                      <input
                        v-model="form.firstName"
                        placeholder="First Name"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Last Name</label>
                      <input
                        v-model="form.lastName"
                        placeholder="Last Name"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Middle Name</label>
                      <input
                        v-model="form.middleName"
                        placeholder="Middle Name"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Nickname</label>
                      <input
                        v-model="form.nickName"
                        placeholder="Nickname"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Gender</label>
                      <select
                        v-model="form.gender"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Gender</option>
                        <option
                          v-for="item in picklists.gender"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Date of Birth</label>
                      <input
                        v-model="form.birth_date"
                        type="date"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Country of Birth</label>
                      <select
                        v-model="form.country_of_birth"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Country</option>
                        <option
                          v-for="item in picklists.country"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Marital Status</label>
                      <select
                        v-model="form.marital_status"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Marital Status</option>
                        <option
                          v-for="item in picklists.marital_status"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Nationality</label>
                      <select
                        v-model="form.nationality"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Nationality</option>
                        <option
                          v-for="item in picklists.nationality"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Bank Name</label>
                      <input
                        v-model="form.bank_name"
                        placeholder="Bank Name"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Account Number</label>
                      <input
                        v-model="form.account_number"
                        placeholder="Account Number"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                  </div>

                  <!-- EMPLOYMENT TAB -->
                  <div
                    v-if="activeTab === 'employment'"
                    class="grid md:grid-cols-2 gap-4"
                  >
                    <div>
                      <label class="label-style">Company Code</label>

                      <select
                        v-model="form.company_code"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Company</option>

                        <option
                          v-for="item in picklists.company_code"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.code }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Department Code</label>
                      <select
                        v-model="form.department_code"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Department Code</option>
                        <option
                          v-for="item in picklists.department"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.code }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Department Name</label>
                      <select
                        v-model="form.department_code"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Department Name</option>
                        <option
                          v-for="item in picklists.department"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Job Code</label>
                      <select
                        v-model="form.job_code"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select JobCode</option>
                        <option
                          v-for="item in picklists.job_code"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.code }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Job Title</label>
                      <select
                        v-model="form.jobTitle"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Job Title</option>
                        <option
                          v-for="item in picklists.job_code"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Business Title</label>
                      <input
                        v-model="form.business_title"
                        placeholder="Business Title"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Shift Code</label>
                      <select
                        v-model="form.shiftCode"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Shift Code</option>

                        <option
                          v-for="shift in shiftCodes"
                          :key="shift.id"
                          :value="shift.shiftCode"
                        >
                          {{ shift.shiftCode }} ({{ shift.shiftStart }} -
                          {{ shift.shiftEnd }})
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Manager ID</label>
                      <input
                        v-model="form.manager_empnum"
                        placeholder="Manager empnum"
                        type="text"
                        @input="searchManager"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>

                    <div>
                      <label class="label-style">Manager Name</label>
                      <input
                        v-model="form.manager_name"
                        placeholder="Manager name"
                        type="text"
                        readonly
                        class="w-full px-3 py-2 rounded-lg border bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                      />
                    </div>
                    <div>
                      <label class="label-style">Employee Class</label>
                      <select
                        v-model="form.employee_class"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Employee Class</option>
                        <option
                          v-for="item in picklists.employee_class"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Employee Type</label>
                      <select
                        v-model="form.employeeType"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Employee Type</option>
                        <option
                          v-for="item in picklists.employee_type"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                    <div>
                      <label class="label-style">Standard Hours</label>
                      <input
                        v-model="form.standard_hours"
                        placeholder="Standard Hours"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Hire Date </label>
                      <input
                        v-model="form.hire_date"
                        type="date"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style">Employee Status</label>
                      <select
                        v-model="form.status"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      >
                        <option disabled value="">Select Employee Status</option>
                        <option
                          v-for="item in picklists.employee_status"
                          :key="item.id"
                          :value="item.code"
                        >
                          {{ item.label }}
                        </option>
                      </select>
                    </div>
                  </div>

                  <!-- CONTACT TAB -->
                  <div v-if="activeTab === 'contact'" class="grid md:grid-cols-2 gap-4">
                    <div>
                      <label class="label-style"> Email Address </label>
                      <input
                        v-model="form.email"
                        type="email"
                        placeholder="Email"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Phone </label>
                      <input
                        v-model="form.phone"
                        placeholder="Phone"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Mobile </label>
                      <input
                        v-model="form.mobile"
                        placeholder="Mobile Number"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Contact Person </label>
                      <input
                        v-model="form.contact_person"
                        placeholder="Contact Person"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Contact Person Number </label>
                      <input
                        v-model="form.contact_person_number"
                        placeholder="Contact Person Number"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                  </div>

                  <!-- ADDRESS TAB -->
                  <div v-if="activeTab === 'address'" class="grid md:grid-cols-2 gap-4">
                    <div>
                      <label class="label-style"> Address Line 1 </label>
                      <input
                        v-model="form.address_line1"
                        placeholder="Address line 1"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Address Line 2 </label>
                      <input
                        v-model="form.address_line2"
                        placeholder="Address line 2"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> City </label>
                      <input
                        v-model="form.city"
                        placeholder="City"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Province </label>
                      <input
                        v-model="form.province"
                        placeholder="Province"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Postal Code </label>
                      <input
                        v-model="form.postal_code"
                        placeholder="Postal Code"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Country </label>
                      <input
                        v-model="form.country"
                        placeholder="Country"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                  </div>

                  <!-- COMPENSATION TAB -->
                  <div
                    v-if="activeTab === 'compensation'"
                    class="grid md:grid-cols-2 gap-4"
                  >
                    <div>
                      <label class="label-style"> Base Salary </label>
                      <input
                        v-model="form.salary"
                        placeholder="Base Salary"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Pay Grade </label>
                      <input
                        v-model="form.payGrade"
                        placeholder="Pay Grade"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Pay Type </label>
                      <input
                        v-model="form.pay_type"
                        placeholder="Pay Type"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                    <div>
                      <label class="label-style"> Factor</label>
                      <input
                        v-model="form.factor"
                        placeholder="Factor"
                        type="text"
                        class="w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                      />
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t dark:border-gray-700 flex justify-between">
                  <div>
                    <button
                      v-if="tabs.findIndex((t) => t.key === activeTab) > 0"
                      @click="
                        activeTab =
                          tabs[tabs.findIndex((t) => t.key === activeTab) - 1].key
                      "
                      class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200"
                    >
                      Previous
                    </button>
                  </div>

                  <div class="space-x-2">
                    <button
                      type="button"
                      @click="showModal = false"
                      class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200"
                    >
                      Cancel
                    </button>

                    <button
                      v-if="tabs.findIndex((t) => t.key === activeTab) < tabs.length - 1"
                      @click="
                        activeTab =
                          tabs[tabs.findIndex((t) => t.key === activeTab) + 1].key
                      "
                      class="px-4 py-2 bg-blue-600 text-white rounded-lg"
                    >
                      Next
                    </button>

                    <button
                      v-else
                      @click="submit"
                      class="px-4 py-2 bg-green-600 text-white rounded-lg"
                    >
                      Save Employee
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </div>

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
              Import Employees
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
              Make sure your file matches the employee import template.
            </p>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t dark:border-gray-700 flex justify-end gap-2">
            <a
              href="/templates/employees_import_template.xlsx"
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
              @click="importEmployees"
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
