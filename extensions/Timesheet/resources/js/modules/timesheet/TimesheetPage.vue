<template>
  <div>
    <h1>Timesheet</h1>
    <form @submit.prevent="submit">
      <input type="date" v-model="form.date" required />
      <input type="number" v-model="form.hours" step="0.25" placeholder="Hours" required />
      <input type="text" v-model="form.description" placeholder="Description" />
      <button>Submit</button>
    </form>

    <h2>My Entries</h2>
    <ul>
      <li v-for="entry in entries" :key="entry.id">
        {{ entry.date }} - {{ entry.hours }}h - {{ entry.status }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const form = ref({
  date: '',
  hours: '',
  description: '',
})

const entries = ref([])

const submit = async () => {
  await axios.post('/api/timesheet', form.value)
  load()
}

const load = async () => {
  const res = await axios.get('/api/timesheet')
  entries.value = res.data
}

onMounted(load)
</script>
