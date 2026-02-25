<template>
  <main class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 py-10">
    <section class="mx-auto max-w-4xl px-4 sm:px-6">
      <header class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs uppercase tracking-[0.2em] text-indigo-300/90">Productivity</p>
          <h1 class="mt-2 text-3xl font-semibold text-white sm:text-4xl">Task Manager</h1>
          <p class="mt-2 text-sm text-slate-300">Track, update, and finish your tasks in one place.</p>
        </div>
        <div class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm text-slate-100 backdrop-blur">
          Total tasks: <strong class="ml-1 font-semibold text-white">{{ totalTasks }}</strong>
        </div>
      </header>

      <div class="mb-4 flex items-center gap-3">
        <label for="status-filter" class="text-sm font-medium text-slate-200">Filter</label>
        <select
          id="status-filter"
          v-model="selectedStatus"
          class="rounded-lg border border-white/25 bg-white/10 px-3 py-2 text-sm text-white backdrop-blur outline-none"
        >
          <option class="text-slate-900" value="all">All</option>
          <option class="text-slate-900" value="pending">Pending</option>
          <option class="text-slate-900" value="in_progress">In Progress</option>
          <option class="text-slate-900" value="done">Done</option>
        </select>
        <p v-if="selectedStatus !== 'all'" class="text-xs text-slate-300">Drag-and-drop is only enabled on All tasks.</p>
        <p v-else-if="hasMore" class="text-xs text-slate-300">Scroll to load more tasks. Reordering unlocks after all tasks are loaded.</p>
      </div>

      <div class="rounded-2xl border border-white/20 bg-white/80 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur">
        <task-form :users="users" @task-added="() => fetchTasks(true)" />
      </div>

      <div class="mt-5 space-y-4">
        <draggable
          v-model="tasks"
          item-key="id"
          :disabled="selectedStatus !== 'all' || hasMore"
          @end="onReorder"
          class="space-y-4"
        >
          <template #item="{ element }">
            <div :class="{ 'task-fade-in': newlyLoadedIds.includes(element.id) }">
              <task-item :task="element" :users="users" @updated="() => fetchTasks(true)" />
            </div>
          </template>
        </draggable>

        <p v-if="tasks.length === 0 && !isLoading" class="rounded-2xl border border-dashed border-white/35 bg-white/10 p-8 text-center text-sm text-slate-200 backdrop-blur">
          No tasks yet. Add your first task above.
        </p>

        <div v-if="isLoading" class="rounded-xl border border-white/20 bg-white/10 p-4 text-center text-sm text-slate-200 backdrop-blur">
          Loading tasks...
        </div>

        <div ref="scrollSentinel" class="h-3"></div>
      </div>
    </section>
  </main>
</template>

<script>
import TaskItem from './TaskItem.vue';
import TaskForm from './TaskForm.vue';
import draggable from 'vuedraggable';
import axios from 'axios';

export default {
  components: { TaskItem, TaskForm, draggable },
  data() {
    return {
      tasks: [],
      users: [],
      selectedStatus: 'all',
      page: 1,
      perPage: 12,
      hasMore: true,
      isLoading: false,
      totalTasks: 0,
      observer: null,
      newlyLoadedIds: [],
    }
  },
  watch: {
    selectedStatus() {
      this.fetchTasks(true);
    },
  },
  methods: {
    async fetchTasks(reset = false) {
      if (this.isLoading) {
        return;
      }

      if (reset) {
        this.page = 1;
        this.hasMore = true;
        this.tasks = [];
      }

      if (!this.hasMore) {
        return;
      }

      const params = this.selectedStatus === 'all' ? {} : { status: this.selectedStatus };
      params.page = this.page;
      params.per_page = this.perPage;

      this.isLoading = true;

      try {
        const response = await axios.get('/tasks', { params });
        const payload = response.data;
        const nextItems = payload.data ?? [];

        this.newlyLoadedIds = nextItems.map(task => task.id);

        this.tasks = reset ? nextItems : [...this.tasks, ...nextItems];
        this.totalTasks = payload.total ?? this.tasks.length;
        this.hasMore = Boolean(payload.next_page_url);
        this.page += 1;

        setTimeout(() => {
          this.newlyLoadedIds = [];
        }, 500);
      } finally {
        this.isLoading = false;
      }
    },
    async fetchUsers() {
      const response = await axios.get('/users');
      this.users = response.data;
    },
    async onReorder() {
      if (this.selectedStatus !== 'all' || this.hasMore) {
        return;
      }

      await axios.post('/tasks/reorder', {
        ordered_ids: this.tasks.map(task => task.id),
      });
      await this.fetchTasks(true);
    },
    setupScrollObserver() {
      this.observer = new IntersectionObserver(
        (entries) => {
          const [entry] = entries;
          if (entry?.isIntersecting) {
            this.fetchTasks();
          }
        },
        {
          rootMargin: '250px',
        }
      );

      if (this.$refs.scrollSentinel) {
        this.observer.observe(this.$refs.scrollSentinel);
      }
    },
  },
  async mounted() {
    await Promise.all([this.fetchTasks(true), this.fetchUsers()]);
    this.setupScrollObserver();
  },
  beforeUnmount() {
    if (this.observer) {
      this.observer.disconnect();
    }
  }
}
</script>

<style scoped>
.task-fade-in {
  animation: task-fade-in 0.45s ease-out;
}

@keyframes task-fade-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
