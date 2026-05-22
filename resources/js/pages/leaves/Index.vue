<script setup lang="ts">
import { reactive, computed, ref } from 'vue';
import axios from 'axios';
import Vue3Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

// Props types
interface LeaveType {
  id: number;
  name: string;
}

interface Balance {
  leave_type_id: number;
  balance: number;
}

interface Props {
  leaveTypes: LeaveType[];
  balances: Balance[];
}

const props = defineProps<Props>();

// Form state
interface LeaveForm {
  leave_type_id: number | '';
  dateRange: (string | null)[];
  days: number | '';
  reason: string;
  attachment: File | null;
}

const form = reactive<LeaveForm>({
  leave_type_id: '',
  dateRange: [null, null],
  days: '',
  reason: '',
  attachment: null,
});

// Errors
interface Errors {
  leave_type_id?: string;
  start_date?: string;
  end_date?: string;
  days?: string;
  reason?: string;
}

const errors = reactive<Errors>({});

// Submitting state
const submitting = ref(false);

// Computed current balance
const currentBalance = computed<number>(() => {
  const balance = props.balances.find(b => b.leave_type_id === form.leave_type_id);
  return balance ? balance.balance : 0;
});

// Update balance when leave type changes
function updateBalance() {
  form.days = '';
}

// Handle file selection
function handleFile(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.attachment = target.files[0];
  } else {
    form.attachment = null;
  }
}

// Submit form
async function submit() {
  submitting.value = true;

  const payload = new FormData();
  payload.append('leave_type_id', form.leave_type_id.toString());
  payload.append('start_date', form.dateRange[0] ?? '');
  payload.append('end_date', form.dateRange[1] ?? '');
  payload.append('days', form.days.toString());
  payload.append('reason', form.reason);
  if (form.attachment) payload.append('attachment', form.attachment);

  try {
    await axios.post('/leaves', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    window.location.href = '/leaves';
  } catch (err: any) {
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors);
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="p-6 max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow">
    <h1 class="text-2xl font-semibold mb-6 text-gray-800 dark:text-gray-200">File a Leave</h1>

    <form @submit.prevent="submit">
      <!-- Leave Type -->
      <div class="mb-4">
        <label class="block text-gray-700 dark:text-gray-300 mb-1" for="leave_type">Leave Type</label>
        <select
          id="leave_type"
          v-model="form.leave_type_id"
          @change="updateBalance"
          class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200"
        >
          <option value="" disabled>Select leave type</option>
          <option v-for="type in leaveTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
        </select>
        <p class="text-sm text-gray-500 mt-1">Balance: {{ currentBalance }}</p>
        <span v-if="errors.leave_type_id" class="text-red-500 text-sm">{{ errors.leave_type_id }}</span>
      </div>

      <!-- Date Range -->
      <div class="mb-4">
        <label class="block text-gray-700 dark:text-gray-300 mb-1">Date Range</label>
        <vue3-datepicker
          v-model="form.dateRange"
          :range="true"
          :format="'YYYY-MM-DD'"
          :calendar="2"
          class="w-full dark:bg-gray-700 dark:text-gray-200"
        />
        <span v-if="errors.start_date" class="text-red-500 text-sm">{{ errors.start_date }}</span>
        <span v-if="errors.end_date" class="text-red-500 text-sm">{{ errors.end_date }}</span>
      </div>

      <!-- Days -->
      <div class="mb-4">
        <label class="block text-gray-700 dark:text-gray-300 mb-1">Days</label>
        <input
          type="number"
          step="0.5"
          min="0.5"
          v-model.number="form.days"
          class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200"
        />
        <span v-if="errors.days" class="text-red-500 text-sm">{{ errors.days }}</span>
      </div>

      <!-- Reason -->
      <div class="mb-4">
        <label class="block text-gray-700 dark:text-gray-300 mb-1">Reason</label>
        <textarea
          v-model="form.reason"
          class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200"
          rows="3"
        ></textarea>
        <span v-if="errors.reason" class="text-red-500 text-sm">{{ errors.reason }}</span>
      </div>

      <!-- Attachment -->
      <div class="mb-4">
        <label class="block text-gray-700 dark:text-gray-300 mb-1">Attachment (optional)</label>
        <input type="file" @change="handleFile" class="w-full dark:text-gray-200" />
      </div>

      <!-- Submit -->
      <div class="flex justify-end">
        <button
          type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded"
          :disabled="submitting"
        >
          {{ submitting ? 'Submitting...' : 'Submit' }}
        </button>
      </div>
    </form>
  </div>
</template>


