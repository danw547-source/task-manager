<template>
  <div class="space-y-4 lg:space-y-5">
    <div class="tasks-toolbar mb-4 rounded-2xl px-5 py-4 shadow-sm backdrop-blur-sm">
      <div>
        <h3 class="mb-1 text-white font-semibold tracking-tight">Task Workspace</h3>
        <p class="text-muted mb-0 text-sm">Track priorities, review updates, and manage work in one place.</p>
      </div>
      <div class="tasks-toolbar-actions gap-3">
        <user-filter-select
          v-model="selectedUserId"
          :is-admin="isAdmin"
          all-label="All users"
          storage-key="taskflow_tasks_selected_user"
          class="mr-0 mb-0"
          @change="onTaskUserSelectionChange"
        />
        <base-button
          type="default"
          class="tasks-create-btn mb-0 text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
          @click="openCreateDialog"
        >
          New Task
        </base-button>
      </div>
    </div>

    <modal
      :show.sync="showCreateDialog"
      type="notice"
      :show-close="true"
      :centered="true"
      modal-classes="modal-2x"
      modal-content-classes="bg-dark"
    >
      <template slot="header">
        <h4 class="modal-title text-white mb-0">Create New Task</h4>
      </template>

      <div class="row">
        <div class="col-md-8">
          <base-input
            label="Title"
            type="text"
            placeholder="Task title"
            v-model="createForm.title"
            required
          />
        </div>
        <div class="col-md-4">
          <base-input
            label="Due Date"
            type="date"
            v-model="createForm.due_date"
            :min="todayDateString"
            required
          />
        </div>
      </div>

      <base-input
        label="Description"
        type="textarea"
        placeholder="Short details about this task"
        v-model="createForm.description"
        required
      />

      <div class="text-danger" v-if="createError">{{ createError }}</div>

      <template slot="footer">
        <base-button
          type="default"
          class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
          @click="showCreateDialog = false"
        >
          Cancel
        </base-button>
        <base-button
          type="default"
          class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
          :disabled="creating"
          @click="submitTask"
        >
          {{ creating ? "Creating..." : "Create Task" }}
        </base-button>
      </template>
    </modal>

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
                <h6 class="title mb-1 text-white">Outstanding Tasks</h6>
                <p class="card-category mb-0 text-slate-300/80">Open and in progress</p>
              </div>
              <span class="outstanding-pill shadow-sm">{{ outstandingTasks }} active</span>
            </div>
          </template>
          <div class="table-full-width outstanding-tasks-wrap mt-1">
            <task-list
              ref="outstandingTaskList"
              :outstanding-only="true"
              :mine-only="shouldScopeToMine"
              :owner-user-id="selectedOwnerUserId"
              :per-page="30"
            />
          </div>
        </card>
      </div>
    </div>
  </div>
</template>

<script>
import TaskList from "@/pages/Dashboard/TaskList";
import Modal from "@/components/Modal";
import UserFilterSelect from "@/components/UserFilterSelect.vue";
import { createTask, getTasks } from "@/services/taskService";
import { getStoredUser } from "@/services/authService";

export default {
  name: "tasks-page",
  components: {
    TaskList,
    Modal,
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
    };
  },
  computed: {
    todayDateString() {
      const today = new Date();
      const year = today.getFullYear();
      const month = `${today.getMonth() + 1}`.padStart(2, "0");
      const day = `${today.getDate()}`.padStart(2, "0");

      return `${year}-${month}-${day}`;
    },
    isAdmin() {
      return this.currentUser?.role === "admin";
    },
    shouldScopeToMine() {
      return !this.isAdmin;
    },
    selectedOwnerUserId() {
      if (!this.isAdmin || !this.selectedUserId) {
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
    dueSoonTasks() {
      const today = new Date();
      const inThreeDays = new Date();
      inThreeDays.setDate(today.getDate() + 3);

      return this.allTasks.filter((task) => {
        if (!task.due_date || task.status === "done") {
          return false;
        }

        const dueDate = new Date(task.due_date);

        return dueDate >= today && dueDate <= inThreeDays;
      }).length;
    },
    outstandingTasks() {
      return this.totalTasks - this.completedTasks;
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
          label: "Due in 3 Days",
          value: this.dueSoonTasks,
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
    async loadOverviewTasks() {
      try {
        const collected = [];
        let page = 1;
        let lastPage = 1;

        do {
          const { data, pagination } = await getTasks({
            page,
            per_page: 50,
            mine: this.shouldScopeToMine ? 1 : 0,
            ...(this.selectedOwnerUserId ? { user_id: this.selectedOwnerUserId } : {}),
          });

          collected.push(...(data || []));
          lastPage = pagination?.last_page || page;
          page += 1;
        } while (page <= lastPage);

        this.allTasks = collected;
      } catch (error) {
        this.allTasks = [];
      }
    },
    async submitTask() {
      this.createError = "";
      const title = this.createForm.title.trim();
      const description = this.createForm.description.trim();
      const dueDate = this.createForm.due_date;

      if (!title) {
        this.createError = "Task title is required.";
        return;
      }

      if (!description) {
        this.createError = "Task description is required.";
        return;
      }

      if (!dueDate) {
        this.createError = "Task due date is required.";
        return;
      }

      if (dueDate < this.todayDateString) {
        this.createError = "Task due date cannot be in the past.";
        return;
      }

      this.creating = true;

      try {
        await createTask({
          title,
          description,
          due_date: dueDate,
        });

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

        this.$notify({
          type: "success",
          message: "Task created successfully.",
          timeout: 2500,
        });
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
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.15rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
}

.tasks-toolbar-actions {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.5rem;
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
  overflow-x: hidden;
  margin-top: 2px;
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
}

.outstanding-card ::v-deep .card-body {
  min-height: calc(100vh - 290px);
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
