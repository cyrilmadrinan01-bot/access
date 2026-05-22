<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import SectionHeader from "@/components/SectionHeader.vue";
import { ref, watch, computed, reactive } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { BreadcrumbItem } from "@/types";
import axios from "axios";

type SectionData = {
  // employment
  company_code?: string;
  department_code?: string;
  job_code?: string;
  manager_empnum?: string;
  status?: string;
  hire_date?: string;

  // compensation
  base_salary?: number;
  pay_grade?: string;
  pay_type?: string;
  factor?: number;
  benefits?: Array<{
    id: number | null;
    name: string;
    amount: number;
    frequency: string;
    taxable: number;
  }>;

  // personal
  first_name?: string;
  last_name?: string;
  gender?: string;
  birth_date?: string;
  bank_name?: string;
  account_number?: string;

  // contact
  email?: string;
  phone?: string;
  mobile?: string;
  contact_person?: string;
  contact_person_number?: string;

  // address
  address_line1?: string;
  address_line2?: string;
  city?: string;
  province?: string;
  country?: string;
  postal_code?: string;

  // government
  tin?: string;
  sss?: string;
  pagibig?: string;
  philhealth?: string;
};

interface Manager {
  empnum: string;
  name: string;
}

const props = defineProps<{
  employee: any;
  asOfDate: string;
  permissions: Record<string, boolean>;
  managers: Array<{ empnum: string; name: string }>;
}>();

// Tracks which section is being edited
const currentSection = ref<string>("government");

// Data of the section currently being edited
//const sectionData = ref<Record<string, any>>({});
const sectionData = reactive<Record<string, any>>({
  company_code: "",
  department_code: "",
  job_code: "",
  manager_empnum: "",
  status: "ACTIVE",
  hire_date: "",
  effective_start: "",
  termination_date: "",
  benefits: [],
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Employees", href: route("employees.index") },
  { title: "Employee Profile", href: "#" },
];

/* --------------------------------------------------
   REUSABLE MODAL STATE
-------------------------------------------------- */

const showModal = ref(false);
const activeSection = ref<string | null>(null);
const editableData = ref<Record<string, any>>({});
const effectiveSet = ref(false);

/* --------------------------------------------------
   SECTION → DATA MAPPING
-------------------------------------------------- */

const sectionMap: Record<string, any> = {
  employment: () => props.employee.employment,
  compensation: () => ({
    ...props.employee.compensation,
    benefits: props.employee.benefits ?? [],
  }),
  personal: () => props.employee.personal,
  contact: () => props.employee.contact,
  address: () => props.employee.address,
  government: () => props.employee.national_ids,
};

const editableFields: Record<string, string[]> = {
  government: ["tin", "sss", "pagibig", "philhealth"],
  employment: [
    "company_code",
    "department_code",
    "job_code",
    "manager_empnum",
    "status",
    "hire_date",
    "termination_date",

    "business_unit",
    "division",
    "cost_center",
    "channel_code",
    "line_code",
    "project",
    "account_code",
    "intercompany",
    "regular_temp",

    "effective_start",
  ],
  compensation: ["base_salary", "pay_grade", "pay_type", "factor", "effective_start"],
  personal: [
    "first_name",
    "last_name",
    "gender",
    "birth_date",
    "bank_name",
    "account_number",

    "marital_status",
    "salutation",
    "prefix",
    "nationality",
    "religion",
    "country_of_birth",

    "effective_start",
  ],
  contact: ["email", "phone", "mobile", "contact_person", "contact_person_number"],
  address: [
    "address_line1",
    "address_line2",
    "type",
    "city",
    "province",
    "country",
    "postal_code",
    "effective_start",
  ],
};

/* --------------------------------------------------
   OPEN MODAL
-------------------------------------------------- */

const openSection = (section: string) => {
  if (!props.permissions?.[section]) return;

  currentSection.value = section;
  activeSection.value = section;

  const data = sectionMap[section]?.() ?? {};

  // Reset sectionData
  Object.keys(sectionData).forEach((key) => delete sectionData[key]);

  editableFields[section]?.forEach((field) => {
    sectionData[field] = data?.[field] ?? "";
  });

  // Compensation benefits
  if (section === "compensation") {
    sectionData.benefits = data?.benefits
      ? JSON.parse(JSON.stringify(data.benefits))
      : [];
  }

  // Reset effective start flag
  effectiveSet.value = false;

  showModal.value = true;

  // Employment-specific
  if (section === "employment") {
    loadManagers();
    managerSearch.value = sectionData.manager_empnum ?? "";
    filteredManagers.value = [];
  }
};

/* --------------------------------------------------
   SAVE HANDLER
-------------------------------------------------- */

const saveSection = () => {
  const payload: Record<string, any> = {};

  editableFields[currentSection.value]?.forEach((field) => {
    payload[field] = sectionData[field as keyof SectionData];
  });

  // Map effective_date → backend field if needed
  if (currentSection.value === "employment") {
    payload.effective_start = sectionData.effective_start; // make sure key matches backend
  }

  if (currentSection.value === "compensation") {
    payload.benefits = sectionData.benefits ?? [];
  }

  router.put(
    route(`employees.update.${currentSection.value}`, props.employee.empnum),
    payload,
    {
      preserveScroll: true,
      onSuccess: () => (showModal.value = false),
      onError: (errors) => {
        console.log("Validation errors:", errors);
        alert(JSON.stringify(errors));
      },
    }
  );
};

/* --------------------------------------------------
   AS-OF DATE RELOAD
-------------------------------------------------- */

const selectedDate = ref(props.asOfDate);

watch(selectedDate, (value) => {
  if (value === props.asOfDate) return;

  router.get(
    route("employees.profile", props.employee.empnum),
    { date: value },
    {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    }
  );
});

/* --------------------------------------------------
   HELPERS
-------------------------------------------------- */

const formatCurrency = (value: number | string | null | undefined): string => {
  const amount = Number(value ?? 0);
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(amount);
};

const formatLabel = (key: string) => key.replace(/_/g, " ").toUpperCase();

// Manager search
const managerSearch = ref(""); // ref is correct here
const filteredManagers = ref<Manager[]>([]); // also a ref
const allManagers = ref<Manager[]>([]); // all managers fetched from API

// Manager search helpers
function filterManagers() {
  const search = managerSearch.value.toLowerCase();
  filteredManagers.value = allManagers.value.filter(
    (m: Manager) =>
      m.empnum.toLowerCase().includes(search) || m.name.toLowerCase().includes(search)
  );
}

// Select manager from dropdown
function selectManager(manager: Manager) {
  sectionData.manager_empnum = manager.empnum;
  managerSearch.value = manager.empnum;
  filteredManagers.value = [];
}

// Fetch managers on modal open
function loadManagers() {
  axios
    .get("/api/employees")
    .then((res) => (allManagers.value = res.data))
    .catch((err) => console.error("Failed to load managers:", err));
}

function addBenefit() {
  sectionData.benefits.push({
    id: null,
    name: "",
    amount: 0,
    frequency: "Monthly",
    taxable: 0,
  });
}

function removeBenefit(index: number) {
  sectionData.benefits.splice(index, 1);
}

const showTerminateModal = ref(false);

const terminationForm = reactive({
  empnum: props.employee.empnum,
  employee_name: props.employee.name,
  termination_date: "",
  termination_reason: "",
  access_termination_date: "",
});

const openTerminateModal = () => {
  terminationForm.empnum = props.employee.empnum;
  terminationForm.employee_name = props.employee.name;
  showTerminateModal.value = true;
};

const saveTermination = () => {
  router.post(route("employees.terminate", props.employee.empnum), terminationForm, {
    preserveScroll: true,
    onSuccess: () => (showTerminateModal.value = false),
  });
};

const rehireEmployee = () => {
  router.post(
    route("employees.rehire", props.employee.empnum),
    {},
    { preserveScroll: true }
  );
};

/* USE YOUR EXISTING PERMISSION */
const page = usePage();

const user = page.props.auth.user;
const canManageEmployees = user?.permissions?.includes("manage employees") ?? false;
</script>

<template>
  <Head title="Employee Profile" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4">
      <!-- HEADER -->
      <div
        class="flex flex-col gap-4 rounded-xl border bg-card p-6 shadow-sm md:flex-row md:items-center md:justify-between"
      >
        <div>
          <h1 class="text-xl font-semibold tracking-tight">
            {{ employee.name }}
          </h1>

          <p class="text-sm text-muted-foreground">
            Employee No:
            <span class="font-medium">{{ employee.empnum }}</span>
          </p>

          <p class="text-sm text-muted-foreground">
            Status:
            <span class="font-medium">{{ employee.employment?.status }}</span>
          </p>
        </div>

        <div v-if="canManageEmployees">
          <button
            v-if="employee.employment?.status === 'ACTIVE'"
            @click="openTerminateModal"
            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700"
          >
            Terminate
          </button>

          <button
            v-else
            @click="rehireEmployee"
            class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700"
          >
            Rehire
          </button>
        </div>
      </div>

      <!-- EMPLOYMENT SECTION -->

      <section
        class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
      >
        <SectionHeader
          title="EMPLOYMENT INFORMATION"
          :canEdit="permissions.employment"
          @edit="openSection('employment')"
        />

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Company
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.company_code }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Department Code
            </p>
            <p class="text-sm font-medium">
              {{ employee?.deptCode }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Department
            </p>
            <p class="text-sm font-medium">
              {{ employee?.deptName }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Job Code
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.job_code }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Job Title
            </p>
            <p class="text-sm font-medium">
              {{ employee?.jobTitle }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Businee Title
            </p>
            <p class="text-sm font-medium">
              {{ employee?.businessTitle }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Businee Unit
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.business_unit }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Division
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.division }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Cost Center
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.cost_center }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Channel Code
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.channel_code }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Line Code
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.line_code }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Project Code
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.project }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Account Code
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.account_code }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Intercompany Code
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.intercompany }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Employee Class
            </p>
            <p class="text-sm font-medium">
              {{ employee?.employeeClass }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Employee Type
            </p>
            <p class="text-sm font-medium">
              {{ employee?.employeeType }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Manager ID
            </p>
            <p class="text-sm font-medium">
              {{ employee.employment?.manager_empnum }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Shift Code
            </p>
            <p class="text-sm font-medium">
              {{ employee.shift.shift_code }}
              {{
                employee.shift.shift_start ? employee.shift.shift_start.slice(0, 5) : "-"
              }}
              -
              {{ employee.shift.shift_end ? employee.shift.shift_end.slice(0, 5) : "" }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Standard Hours
            </p>
            <p class="text-sm font-medium">
              {{ employee?.standard_hours }}
            </p>
          </div>
          <div>
            <p class="text-xs text-muted-foreground">Hire Date</p>
            <p class="font-semibold">{{ employee.employment?.hire_date }}</p>
          </div>
          <div>
            <p class="text-xs text-muted-foreground">Termination Date</p>
            <p class="font-semibold">{{ employee.employment?.termination_date_date }}</p>
          </div>
          <div>
            <p class="text-xs text-muted-foreground">Effective Date</p>
            <p class="font-semibold">{{ employee.employment?.effective_start }}</p>
          </div>
        </div>
      </section>

      <!-- COMPENSATION + BENEFITS -->
      <section
        class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
      >
        <SectionHeader
          title="COMPENSATION INFORMATION"
          :canEdit="permissions.compensation"
          @edit="openSection('compensation')"
        />

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Base Salary
            </p>
            <p class="text-lg font-semibold">
              {{ formatCurrency(employee.compensation?.base_salary) }}
            </p>
          </div>

          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Pay Grade
            </p>
            <p class="text-sm font-medium">
              {{ employee.compensation?.pay_grade }}
            </p>
          </div>

          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Pay Type
            </p>
            <p class="text-lg font-semibold">
              {{ employee.compensation?.pay_type }}
            </p>
          </div>

          <div class="space-y-1">
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Factor
            </p>
            <p class="text-sm font-medium">
              {{ employee.compensation?.factor }}
            </p>
          </div>
        </div>

        <!-- BENEFITS -->
        <div class="mt-8 border-t pt-6">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-primary">
              Employee Benefits
            </h4>

            <span
              v-if="employee.benefits?.length"
              class="text-xs px-2 py-1 rounded-full bg-primary/10 text-primary font-medium"
            >
              {{ employee.benefits.length }} Benefit{{
                employee.benefits.length > 1 ? "s" : ""
              }}
            </span>
          </div>

          <div
            v-if="employee.benefits && employee.benefits.length"
            class="grid grid-cols-1 gap-4 md:grid-cols-2"
          >
            <div
              v-for="benefit in employee.benefits"
              :key="benefit.id"
              class="relative rounded-xl border bg-gradient-to-br from-muted/40 to-muted/20 p-5 shadow-sm hover:shadow-md transition-all duration-200"
            >
              <!-- Taxable badge -->
              <span
                v-if="benefit.taxable"
                class="absolute top-3 right-3 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300"
              >
                TAXABLE
              </span>

              <div class="space-y-2">
                <p class="text-base font-semibold text-foreground">
                  {{ benefit.name }}
                </p>

                <p class="text-xs uppercase tracking-wide text-muted-foreground">
                  {{ benefit.frequency }}
                </p>

                <p class="text-lg font-bold text-primary">
                  {{ formatCurrency(benefit.amount) }}
                </p>
              </div>
            </div>
          </div>

          <div
            v-else
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
          >
            No benefits assigned.
          </div>
        </div>
      </section>

      <!-- PERSONAL INFORMATION -->
      <section
        class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
      >
        <SectionHeader
          title="PERSONAL INFORMATION"
          :canEdit="permissions.personal"
          @edit="openSection('personal')"
        />

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Salutation</p>
            <p class="text-sm font-medium">{{ employee.personal?.salutation }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">First Name</p>
            <p class="text-sm font-medium">{{ employee.personal?.first_name }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Last Name</p>
            <p class="text-sm font-medium">{{ employee.personal?.last_name }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Prefix</p>
            <p class="text-sm font-medium">{{ employee.personal?.prefix }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Marital Status</p>
            <p class="text-sm font-medium">{{ employee.personal?.marital_status }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Gender</p>
            <p class="text-sm font-medium">{{ employee.personal?.gender }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Birth Date</p>
            <p class="text-sm font-medium">{{ employee.personal?.birth_date }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Country of Birth</p>
            <p class="text-sm font-medium">{{ employee.personal?.country_of_birth }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Nationality</p>
            <p class="text-sm font-medium">{{ employee.personal?.nationality }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Religion</p>
            <p class="text-sm font-medium">{{ employee.personal?.religion }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Bank Name</p>
            <p class="text-sm font-medium">{{ employee.personal?.bank_name }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Account Number</p>
            <p class="text-sm font-medium">{{ employee.personal?.account_number }}</p>
          </div>
        </div>
      </section>

      <!-- CONTACT INFORMATION -->
      <section
        class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
      >
        <SectionHeader
          title="CONTACT INFORMATION"
          :canEdit="permissions.contact"
          @edit="openSection('contact')"
        />

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Email</p>
            <p class="text-sm font-medium">{{ employee.contact?.email }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Mobile</p>
            <p class="text-sm font-medium">{{ employee.contact?.mobile }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Contact Person</p>
            <p class="text-sm font-medium">{{ employee.contact?.contact_person }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Contact Person Number</p>
            <p class="text-sm font-medium">
              {{ employee.contact?.contact_person_number }}
            </p>
          </div>
        </div>
      </section>

      <!-- ADDRESS -->
      <section
        class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
      >
        <SectionHeader
          title="ADDRESS INFORMATION"
          :canEdit="permissions.address"
          @edit="openSection('address')"
        />

        <p class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          {{ employee.address?.address_line1 }}, {{ employee.address?.city }},
          {{ employee.address?.province }}, {{ employee.address?.country }}
          {{ employee.address?.postal_code }}
        </p>
      </section>

      <!-- GOVERNMENT IDs SECTION -->
      <section
        class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
      >
        <SectionHeader
          title="GOVERNMENT IDs"
          :canEdit="permissions.government"
          @edit="openSection('government')"
        />
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">TIN</p>
            <p class="text-sm font-medium">{{ employee.national_ids?.tin }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">SSS</p>
            <p class="text-sm font-medium">{{ employee.national_ids?.sss }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">Pag-IBIG</p>
            <p class="text-sm font-medium">{{ employee.national_ids?.pagibig }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs uppercase text-muted-foreground">PhilHealth</p>
            <p class="text-sm font-medium">{{ employee.national_ids?.philhealth }}</p>
          </div>
        </div>
      </section>

      <!-- REUSABLE EDIT MODAL -->
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
      >
        <div
          class="bg-white dark:bg-gray-900 rounded-xl shadow-lg w-full max-w-4xl p-6 max-h-[85vh] overflow-y-auto"
        >
          <!-- HEADER -->
          <div class="flex items-center justify-between border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Edit {{ activeSection?.toUpperCase() }}
            </h2>
            <button
              @click="showModal = false"
              class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 transition"
            >
              ✕
            </button>
          </div>

          <!-- BODY -->
          <div class="px-6 py-4 max-h-[60vh] overflow-y-auto grid gap-4">
            <!-- EFFECTIVE START FIELD -->
            <div
              v-if="editableFields[currentSection]?.includes('effective_start')"
              class="col-span-full"
            >
              <label
                class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                Effective Date
              </label>
              <input
                type="date"
                v-model="sectionData.effective_start"
                @input="effectiveSet = !!sectionData.effective_start"
                class="w-full rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-primary/50"
              />
            </div>

            <!-- OTHER FIELDS -->
            <div
              v-if="
                !editableFields[currentSection]?.includes('effective_start') ||
                effectiveSet
              "
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
              <!-- MANAGER SEARCH -->
              <div v-if="currentSection === 'employment'" class="col-span-full relative">
                <label
                  class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  Manager Emp #
                </label>
                <input
                  v-model="managerSearch"
                  @input="filterManagers"
                  placeholder="Search Manager Emp #"
                  class="w-full rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-primary/50"
                />
                <p
                  v-if="sectionData.manager_empnum"
                  class="text-xs text-muted-foreground mt-1"
                >
                  Selected Manager:
                  {{
                    allManagers.find((m) => m.empnum === sectionData.manager_empnum)?.name
                  }}
                </p>
                <ul
                  v-if="filteredManagers.length && managerSearch"
                  class="absolute z-10 w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 max-h-48 overflow-y-auto mt-1 rounded-md shadow-lg"
                >
                  <li
                    v-for="manager in filteredManagers"
                    :key="manager.empnum"
                    @click="selectManager(manager)"
                    class="px-3 py-2 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700"
                  >
                    {{ manager.empnum }} - {{ manager.name }}
                  </li>
                </ul>
              </div>

              <!-- LOOP OTHER EDITABLE FIELDS -->
              <div
                v-for="field in editableFields[currentSection]?.filter(
                  (f) => f !== 'effective_start'
                ) ?? []"
                :key="field"
              >
                <label
                  class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ field.replace(/_/g, " ").toUpperCase() }}
                </label>

                <!-- Status Dropdown -->
                <select
                  v-if="field === 'status'"
                  v-model="sectionData[field]"
                  class="w-full rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-primary/50"
                >
                  <option value="Active">ACTIVE</option>
                  <option value="Inactive">INACTIVE</option>
                  <option value="Terminated">TERMINATED</option>
                </select>

                <!-- Date Fields -->
                <input
                  v-else-if="field.includes('date')"
                  type="date"
                  v-model="sectionData[field]"
                  class="w-full rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-primary/50"
                />

                <!-- Default Text/Number Input -->
                <input
                  v-else
                  type="text"
                  v-model="sectionData[field]"
                  class="w-full rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-primary/50"
                />
              </div>

              <!-- COMPENSATION BENEFITS -->
              <div
                v-if="currentSection === 'compensation' && effectiveSet"
                class="col-span-full space-y-4"
              >
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                  Benefits
                </h3>
                <div
                  v-for="(benefit, index) in sectionData.benefits"
                  :key="benefit.id ?? index"
                  class="border rounded-lg p-4 flex flex-col md:flex-row md:items-center gap-2 bg-gray-50 dark:bg-gray-800"
                >
                  <input
                    v-model="benefit.name"
                    type="text"
                    placeholder="Benefit Name"
                    class="flex-1 rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary/50"
                  />
                  <input
                    v-model.number="benefit.amount"
                    type="number"
                    placeholder="Amount"
                    class="w-24 rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary/50"
                  />
                  <select
                    v-model="benefit.frequency"
                    class="w-32 rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary/50"
                  >
                    <option value="MONTHLY">Monthly</option>
                    <option value="QUARTERLY">Quarterly</option>
                    <option value="ANNUALLY">Annually</option>
                  </select>
                  <select
                    v-model.number="benefit.taxable"
                    class="w-32 rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary/50"
                  >
                    <option value="1">Taxable</option>
                    <option value="0">Demenimis</option>
                  </select>
                  <button
                    @click="sectionData.benefits.splice(index, 1)"
                    class="px-3 py-1 rounded-lg bg-red-500 text-white text-sm hover:bg-red-600 transition"
                  >
                    Delete
                  </button>
                </div>
                <button
                  @click="addBenefit"
                  class="mt-2 px-4 py-2 rounded-lg bg-primary text-black text-sm hover:bg-primary/90 transition"
                >
                  Add Benefit
                </button>
              </div>
            </div>
          </div>

          <!-- FOOTER -->
          <div
            class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 dark:bg-gray-800"
          >
            <button
              class="px-4 py-2 rounded-lg text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
              @click="showModal = false"
            >
              Cancel
            </button>
            <button
              class="px-4 py-2 rounded-lg text-sm bg-primary text-black hover:bg-primary/90 transition"
              @click="saveSection"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showTerminateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
    >
      <div class="bg-white dark:bg-gray-900 rounded-xl w-full max-w-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Terminate Employee</h2>

        <div class="space-y-4">
          <div>
            <label class="text-sm">Employee Number</label>
            <input
              v-model="terminationForm.empnum"
              readonly
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div>
            <label class="text-sm">Employee Name</label>
            <input
              v-model="terminationForm.employee_name"
              readonly
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div>
            <label class="text-sm">Termination Date</label>
            <input
              type="date"
              v-model="terminationForm.termination_date"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div>
            <label class="text-sm">Termination Reason</label>
            <input
              v-model="terminationForm.termination_reason"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div>
            <label class="text-sm">Access Termination Date</label>
            <input
              type="date"
              v-model="terminationForm.access_termination_date"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
          <button @click="showTerminateModal = false" class="px-4 py-2 border rounded-lg">
            Cancel
          </button>

          <button
            @click="saveTermination"
            class="px-4 py-2 bg-red-600 text-white rounded-lg"
          >
            Save
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
