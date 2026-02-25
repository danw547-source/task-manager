<template>
  <form @submit.prevent="submitForm" class="grid gap-3 sm:grid-cols-2">
    <input
      v-model="title"
      type="text"
      placeholder="Task title"
      class="w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200"
      required
    />

    <textarea
      v-model="description"
      placeholder="Description (optional)"
      class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200 sm:col-span-2"
    ></textarea>

    <input
      v-model="due_date"
      type="date"
      placeholder="Due date"
      class="w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200"
    />

    <select
      v-model="user_id"
      class="w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200"
    >
      <option :value="null">Unassigned</option>
      <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
    </select>

    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-400/30 transition hover:from-indigo-500 hover:to-violet-500 sm:justify-self-start">
      Add Task
    </button>
  </form>
</template>

<script>
import axios from 'axios'

export default {
  props: {
    users: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      title: '',
      description: '',
      due_date: '',
      user_id: null,
    }
  },
  methods: {
    async submitForm() {
      try {
        await axios.post('/tasks', {
          title: this.title,
          description: this.description,
          due_date: this.due_date || null,
          user_id: this.user_id,
        })

        // Clear form after successful submit
        this.title = ''
        this.description = ''
        this.due_date = ''
        this.user_id = null

        // Emit event so parent (TaskList) reloads tasks
        this.$emit('task-added')
      } catch (error) {
        console.error('Error adding task:', error)
      }
    }
  }
}
</script>
