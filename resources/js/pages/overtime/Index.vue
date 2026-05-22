<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

interface Overtime {
  id: number
  timekeeping_id: number
  overtimeDate: string
  startTime: string
  endTime: string
  hours: number
  reasons: string
  status: string
}

const overtimes = ref<Overtime[]>([])

async function loadOvertime() {
  const res = await axios.get('/overtime')
  overtimes.value = res.data.overtimes
}

onMounted(loadOvertime)

const totalApprovedHours = computed(() => 
  overtimes.value
    .filter(o => o.status === 'Approved')
    .reduce((sum, o) => sum + o.hours, 0)
)
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-4">Overtime Management</h1>
    <p>Total Approved Hours: {{ totalApprovedHours }}</p>

    <table class="w-full table-auto">
      <thead>
        <tr>
          <th>Date</th>
          <th>Start</th>
          <th>End</th>
          <th>Hours</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="o in overtimes" :key="o.id">
          <td>{{ o.overtimeDate }}</td>
          <td>{{ o.startTime }}</td>
          <td>{{ o.endTime }}</td>
          <td>{{ o.hours }}</td>
          <td>{{ o.status }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
