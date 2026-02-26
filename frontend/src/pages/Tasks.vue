<template>
  <div class="space-y-4 lg:space-y-5">
    <div class="tasks-toolbar mb-4 rounded-2xl px-5 py-4 shadow-sm backdrop-blur-sm">
      <div class="tasks-toolbar-top">
        <div>
          <h3 class="mb-1 text-white font-semibold tracking-tight">Task Workspace</h3>
          <p class="text-muted mb-0 text-sm">Track priorities, review updates, and manage work in one place.</p>
        </div>
        <div class="tasks-toolbar-actions">
          <div class="tasks-toolbar-control">
            <span class="tasks-toolbar-label">Scope</span>
            <div class="task-view-toggle" role="group" aria-label="Task scope filter">
              <button
                type="button"
                class="task-view-toggle-btn"
                :class="{ 'is-active': selectedTaskScope === 'all' }"
                @click="setTaskScope('all')"
              >
                All
              </button>
              <button
                type="button"
                class="task-view-toggle-btn"
                :class="{ 'is-active': selectedTaskScope === 'owned' }"
                @click="setTaskScope('owned')"
              >
                I Own
              </button>
              <button
                type="button"
                class="task-view-toggle-btn"
                :class="{ 'is-active': selectedTaskScope === 'following' }"
                @click="setTaskScope('following')"
              >
                I Follow
              </button>
            </div>
          </div>

          <div class="tasks-toolbar-control">
            <span class="tasks-toolbar-label">View</span>
            <div class="task-view-toggle" role="group" aria-label="Task view mode">
              <button
                type="button"
                class="task-view-toggle-btn"
                :class="{ 'is-active': selectedTaskView === 'outstanding' }"
                @click="selectedTaskView = 'outstanding'"
              >
                Outstanding
              </button>
              <button
                type="button"
                class="task-view-toggle-btn"
                :class="{ 'is-active': selectedTaskView === 'completed' }"
                @click="selectedTaskView = 'completed'"
              >
                Completed
              </button>
            </div>
          </div>

          <div class="tasks-toolbar-control">
            <span class="tasks-toolbar-label">Owner</span>
            <user-filter-select
              v-model="selectedUserId"
              :is-admin="isAdmin"
              all-label="All users"
              storage-key="taskflow_tasks_selected_user"
              class="mr-0 mb-0"
              @change="onTaskUserSelectionChange"
            />
          </div>

          <base-button
            type="default"
            class="tasks-create-btn mb-0 text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
            @click="openCreateDialog"
          >
            New Task
          </base-button>
        </div>
      </div>
    </div>

    <task-form-modal
      :show.sync="showCreateDialog"
      title="Create New Task"
      :form-data.sync="createForm"
      :min-date="todayDateString"
      :error-message="createError"
      :submitting="creating"
      submit-text="Create Task"
      submitting-text="Creating..."
      @submit="submitTask"
    />

    <div class="row">
      <div class="col-lg-3 col-md-6" v-for="item in overviewCards" :key="item.label">
        <card class="overview-card rounded-xl border-white/10 shadow-sm">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="card-category mb-1 text-xs uppercase tracking-wide text-slate-300/80">{{ item.label }}</p>
              <h3 class="card-title mb-0 text-white font-semibold">{{ item.value }}</h3>
            </div>
            <span class="overview-icon-wrap ring-1 ring-white/10">
              <i :class="item.icon"></i>
            </span>
          </div>
        </card>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <card type="tasks" class="outstanding-card rounded-2xl shadow-md shadow-black/20">
          <template slot="header">
            <div class="d-flex justify-content-between align-items-center outstanding-header py-1">
              <div>
                <h6 class="title mb-1 text-white">{{ selectedTaskTitle }}</h6>
                <p class="card-category mb-0 text-slate-300/80">{{ selectedTaskSubtitle }}</p>
              </div>
              <span class="outstanding-pill shadow-sm">{{ selectedTaskCountLabel }}</span>
            </div>
          </template>
          <div class="table-full-width outstanding-tasks-wrap mt-1">
            <task-list
              ref="outstandingTaskList"
              :view-mode="selectedTaskView"
              :task-scope="selectedTaskScope"
              :mine-only="shouldScopeToMine"
              :owner-user-id="selectedOwnerUserId"
              :per-page="30"
              @tasks-changed="onTasksChanged"
            />
          </div>
        </card>
      </div>
    </div>
  </div>
</template>

<script>
import TaskList from "@/pages/Dashboard/TaskList";
import TaskFormModal from "@/components/TaskFormModal.vue";
import UserFilterSelect from "@/components/UserFilterSelect.vue";
import { createTask, getTasks } from "@/services/taskService";
import { getStoredUser } from "@/services/authService";
import { getTodayDateString, validateTaskForm } from "@/utils/taskForm";
import { notify } from "@/utils/notify";

export default {
  name: "tasks-page",
  components: {
    TaskList,
    TaskFormModal,
    UserFilterSelect,
  },
  data() {
    return {
      currentUser: null,
      showCreateDialog: false,
      creating: false,
      createError: "",
      createForm: {
        title: "",
        description: "",
        due_date: "",
      },
      allTasks: [],
      selectedUserId: 0,
      loadOverviewRequestVersion: 0,
      selectedTaskView: "outstanding",
      selectedTaskScope: "all",
    };
  },
  computed: {
    todayDateString() {
      return getTodayDateString();
    },
    isAdmin() {
      return this.currentUser?.role === "admin";
    },
    shouldScopeToMine() {
      return false;
    },
    selectedOwnerUserId() {
      if (!this.selectedUserId) {
        return 0;
      }

      return Number(this.selectedUserId);
    },
    totalTasks() {
      return this.allTasks.length;
    },
    completedTasks() {
      return this.allTasks.filter((task) => task.status === "done").length;
    },
    inProgressTasks() {
      return this.allTasks.filter((task) => task.status === "in_progress").length;
    },
    pendingTasks() {
      return this.allTasks.filter((task) => task.status === "pending").length;
    },
    overdueTasks() {
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      return this.allTasks.filter((task) => {
        if (!task.due_date || task.status === "done") {
          return false;
        }

        const dueDate = new Date(task.due_date);
        if (Number.isNaN(dueDate.getTime())) {
          return false;
        }

        dueDate.setHours(0, 0, 0, 0);

        return dueDate < today;
      }).length;
    },
    outstandingTasks() {
      return this.totalTasks - this.completedTasks;
    },
    selectedTaskCountLabel() {
      if (this.selectedTaskView === "completed") {
        return `${this.completedTasks} completed`;
      }

      return `${this.outstandingTasks} active`;
    },
    selectedTaskTitle() {
      return this.selectedTaskView === "completed" ? "Completed Tasks" : "Outstanding Tasks";
    },
    selectedTaskSubtitle() {
      return this.selectedTaskView === "completed"
        ? "Finished work"
        : "Open and in progress";
    },
    overviewCards() {
      return [
        {
          label: "Total Tasks",
          value: this.totalTasks,
          icon: "tim-icons icon-bullet-list-67 text-primary",
        },
        {
          label: "Outstanding",
          value: this.outstandingTasks,
          icon: "tim-icons icon-time-alarm text-warning",
        },
        {
          label: "Completed",
          value: this.completedTasks,
          icon: "tim-icons icon-check-2 text-success",
        },
        {
          label: "Overdue",
          value: this.overdueTasks,
          icon: "tim-icons icon-calendar-60 text-info",
        },
      ];
    },
  },
  async mounted() {
    this.currentUser = getStoredUser();
    await this.loadOverviewTasks();
  },
  methods: {
    openCreateDialog() {
      this.createError = "";
      this.showCreateDialog = true;
    },
    async onTaskUserSelectionChange() {
      await this.loadOverviewTasks();
      if (this.$refs.outstandingTaskList?.loadTasks) {
        await this.$refs.outstandingTaskList.loadTasks();
      }
    },
    async setTaskScope(scope) {
      if (this.selectedTaskScope === scope) {
        return;
      }

      this.selectedTaskScope = scope;
      // Scope changes can make a previously selected owner filter feel confusing,
      // so we reset owner to "All users" for a clean mental model.
      this.selectedUserId = 0;

      await this.loadOverviewTasks();
      if (this.$refs.outstandingTaskList?.loadTasks) {
        await this.$refs.outstandingTaskList.loadTasks();
      }
    },
    async onTasksChanged() {
      await this.loadOverviewTasks();
    },
    async loadOverviewTasks() {
      // Version guard prevents stale responses from slower requests from
      // overwriting newer filter/scope selections.
      const requestVersion = ++this.loadOverviewRequestVersion;

      try {
        const collected = [];
        let page = 1;
        let lastPage = 1;

        do {
          const { data, pagination } = await getTasks({
            page,
            per_page: 50,
            scope: this.selectedTaskScope,
            mine: this.shouldScopeToMine ? 1 : 0,
            ...(this.selectedOwnerUserId ? { user_id: this.selectedOwnerUserId } : {}),
          });

          collected.push(...(data || []));
          lastPage = pagination?.last_page || page;
          page += 1;
        } while (page <= lastPage);

        if (requestVersion !== this.loadOverviewRequestVersion) {
          return;
        }

        this.allTasks = collected;
      } catch (error) {
        if (requestVersion !== this.loadOverviewRequestVersion) {
          return;
        }

        this.allTasks = [];
      }
    },
    async submitTask(submittedForm = this.createForm) {
      this.createError = "";

      const validation = validateTaskForm(submittedForm, this.todayDateString);
      if (!validation.valid) {
        this.createError = validation.error;
        return;
      }

      this.creating = true;

      try {
        await createTask(validation.payload);

        this.createForm = {
          title: "",
          description: "",
          due_date: "",
        };
        this.showCreateDialog = false;

        if (this.$refs.outstandingTaskList?.loadTasks) {
          await this.$refs.outstandingTaskList.loadTasks();
        }

        await this.loadOverviewTasks();

        notify(this, { type: "success", message: "Task created successfully.", timeout: 2500 });
      } catch (error) {
        this.createError = error.message || "Unable to create task.";
      } finally {
        this.creating = false;
      }
    },
  },
};
</script>

<style scoped>
.tasks-toolbar {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  padding: 1rem 1.15rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
}

.tasks-toolbar-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.85rem;
}

.tasks-toolbar-actions {
  display: flex;
  align-items: flex-end;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.6rem;
}

.tasks-toolbar-control {
  display: inline-flex;
  flex-direction: column;
  gap: 0.3rem;
}

.tasks-toolbar-label {
  font-size: 0.66rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: rgba(255, 255, 255, 0.58);
  font-weight: 700;
}

.task-view-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.05);
}

.task-view-toggle-btn {
  border: 0;
  border-radius: 999px;
  padding: 0.35rem 0.75rem;
  background: transparent;
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.78rem;
  font-weight: 600;
}

.task-view-toggle-btn.is-active {
  background: rgba(255, 255, 255, 0.18);
  color: rgba(255, 255, 255, 0.95);
}

@media (max-width: 768px) {
  .tasks-toolbar-top {
    flex-direction: column;
    align-items: flex-start;
  }

  .tasks-toolbar-actions {
    width: 100%;
    align-items: stretch;
    justify-content: flex-start;
  }

  .tasks-toolbar-control {
    width: 100%;
  }

  .tasks-create-btn {
    width: 100%;
  }
}

.tasks-create-btn {
  border-radius: 999px;
  padding-left: 0.95rem;
  padding-right: 0.95rem;
  font-weight: 700;
}

.overview-card {
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
}

.overview-icon-wrap {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.04);
}

.outstanding-tasks-wrap {
  flex: 1 1 auto;
  overflow-x: hidden;
  overflow-y: auto;
  max-height: none;
  min-height: 0;
  margin-top: 2px;
}

.outstanding-card ::v-deep .table-full-width {
  max-height: none !important;
  height: 100%;
}

.outstanding-card ::v-deep .task-table-wrap {
  height: 100%;
}

.outstanding-tasks-wrap ::v-deep .table {
  width: 100%;
  table-layout: fixed;
  margin-bottom: 0;
}

.outstanding-tasks-wrap ::v-deep .table th,
.outstanding-tasks-wrap ::v-deep .table td {
  white-space: normal;
  word-break: break-word;
  vertical-align: top;
}

.outstanding-tasks-wrap ::v-deep .table th:first-child,
.outstanding-tasks-wrap ::v-deep .table td:first-child {
  width: 56px;
}

.outstanding-tasks-wrap ::v-deep .table td {
  padding-top: 1.15rem;
  padding-bottom: 1.15rem;
}

.outstanding-tasks-wrap ::v-deep .title {
  font-size: 1.05rem;
  margin-bottom: 0.35rem;
  color: rgba(255, 255, 255, 0.92);
}

.outstanding-tasks-wrap ::v-deep .text-muted {
  color: rgba(255, 255, 255, 0.56) !important;
}

.outstanding-tasks-wrap ::v-deep .td-actions {
  width: 60px;
}

.outstanding-tasks-wrap ::v-deep .td-actions .btn {
  opacity: 0.65;
}

.outstanding-tasks-wrap ::v-deep .mt-2.d-flex.align-items-center.flex-wrap {
  gap: 0.35rem;
}

.outstanding-tasks-wrap ::v-deep .mt-2.d-flex.align-items-center.flex-wrap .btn {
  margin-right: 0 !important;
  margin-bottom: 0 !important;
}

.outstanding-tasks-wrap ::v-deep input,
.outstanding-tasks-wrap ::v-deep textarea {
  background: rgba(255, 255, 255, 0.02);
  border-color: rgba(255, 255, 255, 0.08);
}

.outstanding-card {
  border: 1px solid rgba(255, 255, 255, 0.05);
  background: rgba(255, 255, 255, 0.02);
  min-height: calc(100vh - 220px);
  display: flex;
  flex-direction: column;
}

.outstanding-card ::v-deep .card-body {
  min-height: calc(100vh - 290px);
  display: flex;
  flex-direction: column;
}

.outstanding-header .title {
  letter-spacing: 0.02em;
  font-size: 1rem;
}

.outstanding-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem 0.7rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.9);
  background: rgba(29, 140, 248, 0.18);
  border: 1px solid rgba(29, 140, 248, 0.4);
}

@media (max-width: 991.98px) {
  .tasks-toolbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .tasks-toolbar-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
