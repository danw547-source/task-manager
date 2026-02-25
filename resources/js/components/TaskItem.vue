<template>
  <article class="rounded-2xl border border-white/20 bg-white/90 p-5 shadow-xl shadow-slate-950/20 backdrop-blur transition hover:bg-white">
    <div v-if="!isEditing">
      <h3 class="text-lg font-semibold text-slate-900">{{ task.title }}</h3>
      <p v-if="task.description" class="mt-2 text-sm leading-6 text-slate-600">{{ task.description }}</p>

      <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
        <span class="rounded-full bg-slate-100 px-3 py-1.5 font-medium text-slate-700">Status: {{ task.status }}</span>
        <span v-if="task.due_date" class="rounded-full bg-indigo-50 px-3 py-1.5 font-medium text-indigo-700">Due: {{ task.due_date }}</span>
        <span class="rounded-full bg-violet-50 px-3 py-1.5 font-medium text-violet-700">Assigned: {{ task.user?.name ?? 'Unassigned' }}</span>
      </div>

      <div class="mt-5 flex flex-wrap gap-2">
        <button @click="startEdit" class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Edit</button>
        <button @click="toggleStatus" class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Toggle Status</button>
        <button @click="deleteTask" class="rounded-lg border border-rose-300 px-3.5 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-50">Delete</button>
      </div>
    </div>

    <div v-else class="space-y-3">
      <input v-model="editTitle" placeholder="Title" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200" />
      <textarea v-model="editDescription" placeholder="Description" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200"></textarea>
      <input v-model="editDueDate" type="date" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200" />
      <select v-model="editUserId" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200">
        <option :value="null">Unassigned</option>
        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
      </select>
      <div class="flex gap-2">
        <button @click="saveEdit" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white transition hover:from-indigo-500 hover:to-violet-500">Save</button>
        <button @click="cancelEdit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
      </div>
    </div>
  </article>
</template>

<script>
import axios from 'axios'

export default {
  props: {
    task: {
      type: Object,
      required: true,
    },
    users: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      isEditing: false,
      editTitle: this.task.title,
      editDescription: this.task.description,
      editDueDate: this.task.due_date,
      editUserId: this.task.user_id,
    }
  },
  methods: {
    startEdit() {
      this.editTitle = this.task.title
      this.editDescription = this.task.description
      this.editDueDate = this.task.due_date
      this.editUserId = this.task.user_id
      this.isEditing = true
    },
    cancelEdit() {
      this.isEditing = false
    },
    async saveEdit() {
      try {
        await axios.put(`/tasks/${this.task.id}`, {
          title: this.editTitle,
          description: this.editDescription,
          due_date: this.editDueDate,
          user_id: this.editUserId,
        })
        this.isEditing = false
        this.$emit('updated')
      } catch (error) {
        console.error('Error updating task:', error)
      }
    },
    async toggleStatus() {
      const nextStatus =
        this.task.status === 'pending'
          ? 'in_progress'
          : this.task.status === 'in_progress'
          ? 'done'
          : 'pending'
      try {
        await axios.put(`/tasks/${this.task.id}`, {
          status: nextStatus
        })
        this.$emit('updated')
      } catch (error) {
        console.error('Error toggling status:', error)
      }
    },
    async deleteTask() {
      try {
        await axios.delete(`/tasks/${this.task.id}`)
        this.$emit('updated')
      } catch (error) {
        console.error('Error deleting task:', error)
      }
    }
  }
}
</script>
