<template>
  <div>
    <div v-if="!tasks.length" class="text-muted py-6 text-sm text-slate-300/80">No tasks available.</div>

    <div v-else class="task-table-wrap">
      <table class="task-table w-100">
        <thead>
          <tr>
            <th class="task-col-title">
              <button type="button" class="task-header-btn" @click="setSort('title')">
                Task Title
                <span class="task-sort-indicator">{{ sortIndicator('title') }}</span>
              </button>
            </th>
            <th>
              <button type="button" class="task-header-btn" @click="setSort('creator_name')">
                Created By
                <span class="task-sort-indicator">{{ sortIndicator('creator_name') }}</span>
              </button>
            </th>
            <th>
              <button type="button" class="task-header-btn" @click="setSort('created_at')">
                Created Date
                <span class="task-sort-indicator">{{ sortIndicator('created_at') }}</span>
              </button>
            </th>
            <th>
              <button type="button" class="task-header-btn" @click="setSort('due_date')">
                Due Date
                <span class="task-sort-indicator">{{ sortIndicator('due_date') }}</span>
              </button>
            </th>
            <th v-if="!isReadOnlyView" class="task-col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="task in tableData"
            :key="task.id"
            class="task-table-row"
            @click="openTaskDetails(task)"
          >
            <td class="task-col-title">
              <div class="task-title-lg">{{ task.title }}</div>
            </td>
            <td>{{ task.creator_name }}</td>
            <td>{{ formatCreatedDate(task.created_at) }}</td>
            <td>{{ formatDueDate(task.due_date) }}</td>
            <td v-if="!isReadOnlyView" class="task-col-actions" @click.stop>
              <div class="task-compact-actions">
                <base-button
                  v-if="canManageTask(task)"
                  type="default"
                  size="sm"
                  class="mb-0 text-white bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-white focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
                  @click.stop="openEditDialog(task)"
                >
                  <span class="mr-1">✎</span>
                  Edit
                </base-button>
                <base-button
                  v-if="canManageTask(task)"
                  type="danger"
                  size="sm"
                  class="mb-0 text-white bg-danger box-border border border-transparent hover:bg-danger-strong focus:ring-4 focus:ring-danger-medium shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
                  @click.stop="openDeleteDialog(task)"
                >
                  <span class="mr-1">🗑</span>
                  Delete
                </base-button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <modal
      :show.sync="showTaskDialog"
      :show-close="true"
      :centered="true"
      modal-classes="modal-lg"
      modal-content-classes="bg-dark"
      body-classes="pt-3"
    >
      <template slot="header">
        <div v-if="activeTask" class="w-100">
          <h4 class="modal-title text-white mb-1 tracking-tight">{{ activeTask.title }}</h4>
          <div class="task-header-meta text-muted text-sm">
            <span v-if="isReadOnlyView" class="task-readonly-badge">Read-only</span>
            <span>Created by {{ activeTask.creator_name }} on {{ formatCreatedDate(activeTask.created_at) }}</span>
          </div>
        </div>
      </template>

      <div v-if="activeTask">
        <p class="text-muted mb-3 leading-relaxed text-sm">{{ activeTask.description }}</p>

        <div class="task-meta-row mb-3">
          <span class="task-meta-pill">Status: {{ formatStatus(activeTask.done ? "done" : "pending") }}</span>
          <span class="task-meta-pill">Due: {{ formatDueDate(activeTask.due_date) }}</span>
        </div>

        <div v-if="!isReadOnlyView" class="mt-2 d-flex align-items-center flex-wrap">
          <base-button
            v-if="canToggleFollow(activeTask)"
            type="info"
            size="sm"
            class="mr-2 mb-2"
            @click="toggleFollow(activeTask)"
          >
            {{ activeTask.is_following ? "Unfollow" : "Follow" }}
            ({{ activeTask.followers_count || 0 }})
          </base-button>

          <base-button
            v-if="canCompleteTask(activeTask)"
            type="success"
            size="sm"
            class="mr-2 mb-2"
            @click="markComplete(activeTask)"
          >
            Mark Complete
          </base-button>
        </div>

        <div class="mt-3">
          <h6 class="mb-2">Conversation</h6>
          <div v-if="!isReadOnlyView">
            <base-input
              placeholder="Write a comment..."
              v-model="commentDrafts[activeTask.id]"
            />
            <div class="d-flex justify-content-end">
              <base-button size="sm" type="primary" @click="submitComment(activeTask)">
                Comment
              </base-button>
            </div>
          </div>

          <div
            v-if="threadedCommentsByTask[activeTask.id] && threadedCommentsByTask[activeTask.id].length"
            class="comment-thread mt-3"
          >
            <div
              v-for="comment in threadedCommentsByTask[activeTask.id].slice(0, 20)"
              :key="comment.id"
              class="comment-node"
            >
              <div class="comment-item" :class="{ 'is-own-comment': isOwnComment(comment) }">
                <div class="comment-avatar" :style="commentAvatarStyle(comment)">
                  {{ commentInitial(comment) }}
                </div>

                <div class="comment-body-wrap">
                  <div class="comment-meta">
                    <span class="comment-author">{{ commentAuthorName(comment) }}</span>
                    <span class="comment-time">{{ formatCommentTime(comment) }}</span>
                  </div>
                  <div class="comment-bubble">{{ comment.body }}</div>

                  <div v-if="!isReadOnlyView" class="comment-actions">
                    <button class="comment-action-btn" type="button" @click="toggleReplyBox(activeTask.id, comment.id)">
                      Reply
                    </button>
                  </div>

                  <div v-if="activeReplyTo[activeTask.id] === comment.id" class="reply-box mt-2">
                    <base-input
                      :ref="replyInputRef(activeTask.id, comment.id)"
                      placeholder="Write a reply..."
                      v-model="replyDrafts[replyDraftKey(activeTask.id, comment.id)]"
                    />
                    <div class="d-flex justify-content-end">
                      <base-button size="sm" type="primary" @click="submitReply(activeTask, comment)">
                        Reply
                      </base-button>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-for="reply in comment.replies || []"
                :key="reply.id"
                class="comment-item comment-reply"
                :class="{ 'is-own-comment': isOwnComment(reply) }"
              >
                <div class="comment-avatar" :style="commentAvatarStyle(reply)">
                  {{ commentInitial(reply) }}
                </div>

                <div class="comment-body-wrap">
                  <div class="comment-meta">
                    <span class="comment-author">{{ commentAuthorName(reply) }}</span>
                    <span class="comment-time">{{ formatCommentTime(reply) }}</span>
                  </div>
                  <div class="comment-bubble">{{ reply.body }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </modal>

    <task-form-modal
      :show.sync="showEditDialog"
      title="Edit Task"
      :form-data.sync="editForm"
      :min-date="todayDateString"
      :due-date-readonly="true"
      due-date-input-type="text"
      :error-message="editError"
      :submitting="editing"
      submit-text="Save"
      submitting-text="Saving..."
      @submit="confirmEditTask"
      @cancel="closeEditDialog"
    />

    <confirm-dialog
      :show.sync="showDeleteDialog"
      title="Delete Task"
      :message="deleteTaskMessage"
      confirm-text="Delete"
      @confirm="confirmDeleteTask"
      @cancel="closeDeleteDialog"
    />
  </div>
</template>

<script>
import Modal from "@/components/Modal";
import TaskFormModal from "@/components/TaskFormModal.vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import {
  addTaskComment,
  deleteTask,
  followTask,
  getTaskComments,
  getTasks,
  getUnreadTaskMessages,
  markTaskMessagesRead,
  unfollowTask,
  updateTask,
} from "@/services/taskService";
import { getStoredUser } from "@/services/authService";
import {
  formatDateForDisplay,
  getTodayDateString,
  normalizeDueDateForInput,
  validateTaskForm,
} from "@/utils/taskForm";
import {
  buildCommentTree,
  commentAuthorName,
  commentAvatarStyle,
  commentInitial,
  formatCommentTime,
} from "@/utils/taskComments";
import { notify } from "@/utils/notify";

export default {
  components: {
    Modal,
    TaskFormModal,
    ConfirmDialog,
  },
  props: {
    viewMode: {
      type: String,
      default: "outstanding",
    },
    taskScope: {
      type: String,
      default: "all",
    },
    mineOnly: {
      type: Boolean,
      default: false,
    },
    ownerUserId: {
      type: Number,
      default: 0,
    },
    perPage: {
      type: Number,
      default: 12,
    },
  },
  data() {
    return {
      tasks: [],
      commentsByTask: {},
      commentDrafts: {},
      replyDrafts: {},
      activeReplyTo: {},
      showTaskDialog: false,
      selectedTaskId: null,
      showEditDialog: false,
      editForm: {
        id: null,
        title: "",
        description: "",
        due_date: "",
      },
      editError: "",
      editing: false,
      showDeleteDialog: false,
      taskPendingDelete: null,
      unreadTotal: 0,
      unreadPollTimer: null,
      currentUser: null,
      sortKey: 'title',
      sortDirection: 'asc',
      loadTasksRequestVersion: 0,
    };
  },
  computed: {
    tableData() {
      const sortedTasks = [...this.tasks];
      const direction = this.sortDirection === 'asc' ? 1 : -1;

      sortedTasks.sort((firstTask, secondTask) => {
        const firstValue = this.getSortableValue(firstTask, this.sortKey);
        const secondValue = this.getSortableValue(secondTask, this.sortKey);

        if (firstValue === secondValue) {
          return 0;
        }

        return firstValue > secondValue ? direction : -direction;
      });

      return sortedTasks;
    },
    threadedCommentsByTask() {
      const threaded = {};

      Object.entries(this.commentsByTask).forEach(([taskId, comments]) => {
        threaded[taskId] = buildCommentTree(comments || []);
      });

      return threaded;
    },
    isAdmin() {
      return this.currentUser?.role === "admin";
    },
    isReadOnlyView() {
      return this.viewMode === "completed";
    },
    activeTask() {
      if (!this.selectedTaskId) {
        return null;
      }

      return this.tasks.find((task) => task.id === this.selectedTaskId) || null;
    },
    deleteTaskMessage() {
      if (!this.taskPendingDelete) {
        return "";
      }

      return `Delete "${this.taskPendingDelete.title}"? This action cannot be undone.`;
    },
    todayDateString() {
      return getTodayDateString();
    },
  },
  async mounted() {
    this.currentUser = getStoredUser();
    await this.loadTasks();
    await this.pollUnreadMessages();
    this.unreadPollTimer = setInterval(this.pollUnreadMessages, 20000);
  },
  beforeDestroy() {
    if (this.unreadPollTimer) {
      clearInterval(this.unreadPollTimer);
    }
  },
  watch: {
    viewMode() {
      this.loadTasks();
    },
    taskScope() {
      this.loadTasks();
    },
    mineOnly() {
      this.loadTasks();
    },
    ownerUserId() {
      this.loadTasks();
    },
    perPage() {
      this.loadTasks();
    },
    showTaskDialog(value) {
      if (!value) {
        this.selectedTaskId = null;
      }
    },
  },
  methods: {
    setSort(sortKey) {
      if (this.sortKey === sortKey) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        return;
      }

      this.sortKey = sortKey;
      this.sortDirection = 'asc';
    },
    sortIndicator(sortKey) {
      if (this.sortKey !== sortKey) {
        return '↕';
      }

      return this.sortDirection === 'asc' ? '↑' : '↓';
    },
    getSortableValue(task, sortKey) {
      if (sortKey === 'created_at' || sortKey === 'due_date') {
        const parsedDate = task[sortKey] ? new Date(task[sortKey]).getTime() : null;

        return Number.isFinite(parsedDate) ? parsedDate : Number.MAX_SAFE_INTEGER;
      }

      return (task[sortKey] || '').toString().toLowerCase();
    },
    async loadTasks() {
      const requestVersion = ++this.loadTasksRequestVersion;
      // `taskScope` is the current API contract. `mineOnly` stays as a fallback
      // for older call sites until they are fully migrated.
      const scope = this.taskScope || (this.mineOnly ? "owned" : "all");

      try {
        const { data } = await getTasks({
          page: 1,
          per_page: this.perPage,
          scope,
          mine: this.ownerUserId ? 0 : this.mineOnly ? 1 : 0,
          ...(this.ownerUserId ? { user_id: this.ownerUserId } : {}),
        });

        if (requestVersion !== this.loadTasksRequestVersion) {
          // Ignore stale responses when user changes filters quickly.
          return;
        }

        const filteredTasks = this.viewMode === "completed"
          ? data.filter((task) => task.status === "done")
          : data.filter((task) => task.status !== "done");

        this.tasks = filteredTasks.map((task) => ({
          id: task.id,
          user_id: task.user_id || task.user?.id || null,
          creator_name: task.user?.name || "Unknown user",
          created_at: task.created_at || null,
          title: task.title,
          description: task.description || "No description",
          due_date: normalizeDueDateForInput(task.due_date),
          status: task.status,
          done: task.status === "done",
          is_following: Boolean(task.is_following),
          followers_count: task.followers_count || 0,
        }));

        await Promise.all(this.tasks.map((task) => this.loadComments(task.id)));
      } catch (error) {
        if (requestVersion !== this.loadTasksRequestVersion) {
          return;
        }

        this.tasks = [];
      }
    },
    openTaskDetails(task) {
      this.selectedTaskId = task.id;
      this.showTaskDialog = true;
    },
    canManageTask(task) {
      if (!task || !this.currentUser?.id) {
        return false;
      }

      if (this.isReadOnlyView) {
        return false;
      }

      return this.isAdmin || task.user_id === this.currentUser.id;
    },
    canCompleteTask(task) {
      if (!task || task.done || this.isReadOnlyView) {
        return false;
      }

      return this.canManageTask(task);
    },
    canToggleFollow(task) {
      if (!task || !this.currentUser?.id || this.isReadOnlyView) {
        return false;
      }

      return task.user_id !== this.currentUser.id;
    },
    formatStatus(status) {
      if (status === "in_progress") {
        return "In Progress";
      }

      if (status === "done") {
        return "Done";
      }

      return "Pending";
    },
    formatCreatedDate(value) {
      return formatDateForDisplay(value, "Unknown date");
    },
    formatDueDate(value) {
      return formatDateForDisplay(value, "N/A");
    },
    async loadComments(taskId) {
      try {
        const { data } = await getTaskComments(taskId, { page: 1, per_page: 20 });
        this.$set(this.commentsByTask, taskId, data || []);
      } catch (error) {
        this.$set(this.commentsByTask, taskId, []);
      }
    },
    async toggleFollow(task) {
      if (!this.canToggleFollow(task)) {
        return;
      }

      if (task.is_following) {
        await unfollowTask(task.id);
        task.is_following = false;
        task.followers_count = Math.max(0, (task.followers_count || 0) - 1);
        notify(this, { type: "warning", message: `You unfollowed "${task.title}".`, timeout: 2200 });
      } else {
        await followTask(task.id);
        task.is_following = true;
        task.followers_count = (task.followers_count || 0) + 1;
        notify(this, { type: "success", message: `You are now following "${task.title}".`, timeout: 2200 });
      }
    },
    async submitComment(task) {
      if (this.isReadOnlyView) {
        return;
      }

      const body = (this.commentDrafts[task.id] || "").trim();

      if (!body) {
        return;
      }

      const created = await addTaskComment(task.id, body);

      if (created && !created.user && this.currentUser) {
        created.user = {
          id: this.currentUser.id,
          name: this.currentUser.name,
        };
      }

      this.commentDrafts[task.id] = "";

      const existing = this.commentsByTask[task.id] || [];
      this.$set(this.commentsByTask, task.id, [created, ...existing]);
    },
    async submitReply(task, parentComment) {
      if (this.isReadOnlyView) {
        return;
      }

      const key = this.replyDraftKey(task.id, parentComment.id);
      const body = (this.replyDrafts[key] || "").trim();

      if (!body) {
        return;
      }

      const created = await addTaskComment(task.id, body, parentComment.id);

      if (created && !created.user && this.currentUser) {
        created.user = {
          id: this.currentUser.id,
          name: this.currentUser.name,
        };
      }

      created.parent_comment_id = parentComment.id;

      const existing = this.commentsByTask[task.id] || [];
      this.$set(this.commentsByTask, task.id, [created, ...existing]);
      this.$set(this.replyDrafts, key, "");
      this.$set(this.activeReplyTo, task.id, null);
    },
    async markComplete(task) {
      if (!this.canCompleteTask(task)) {
        return;
      }

      try {
        await updateTask(task.id, { status: "done" });
        await this.loadTasks();
        this.$emit("tasks-changed");
        notify(this, { type: "success", message: `Task "${task.title}" marked as complete.`, timeout: 2500 });
      } catch (error) {
      }
    },
    openEditDialog(task) {
      if (this.isReadOnlyView) {
        return;
      }

      if (!this.canManageTask(task)) {
        return;
      }

      this.editError = "";
      this.editForm = {
        id: task.id,
        title: task.title,
        description: task.description,
        due_date: normalizeDueDateForInput(task.due_date),
      };
      this.showEditDialog = true;
    },
    closeEditDialog() {
      this.showEditDialog = false;
      this.editError = "";
      this.editForm = {
        id: null,
        title: "",
        description: "",
        due_date: "",
      };
    },
    async confirmEditTask(submittedForm = this.editForm) {
      if (this.isReadOnlyView) {
        return;
      }

      const validation = validateTaskForm(submittedForm, this.todayDateString, {
        requireDueDate: false,
        includeDueDateInPayload: false,
      });
      if (!validation.valid) {
        this.editError = validation.error;
        return;
      }

      this.editing = true;
      this.editError = "";

      try {
        await updateTask(submittedForm.id, {
          ...validation.payload,
        });

        const task = this.tasks.find((entry) => entry.id === submittedForm.id);
        if (task) {
          task.title = validation.payload.title;
          task.description = validation.payload.description;
        }

        this.closeEditDialog();
        await this.loadTasks();
        this.$emit("tasks-changed");
        notify(this, { type: "success", message: "Task updated successfully.", timeout: 2300 });
      } catch (error) {
        this.editError = error.message || "Unable to update task.";
      } finally {
        this.editing = false;
      }
    },
    openDeleteDialog(task) {
      if (this.isReadOnlyView) {
        return;
      }

      if (!this.canManageTask(task)) {
        return;
      }

      this.taskPendingDelete = task;
      this.showDeleteDialog = true;
    },
    closeDeleteDialog() {
      this.showDeleteDialog = false;
      this.taskPendingDelete = null;
    },
    async confirmDeleteTask() {
      if (this.isReadOnlyView) {
        return;
      }

      const task = this.taskPendingDelete;

      if (!task) {
        return;
      }

      try {
        await deleteTask(task.id);
        if (this.selectedTaskId === task.id) {
          this.showTaskDialog = false;
          this.selectedTaskId = null;
        }

        this.closeDeleteDialog();
        await this.loadTasks();
        this.$emit("tasks-changed");
        notify(this, { type: "warning", message: `Task "${task.title}" deleted.`, timeout: 2500 });
      } catch (error) {
      }
    },
    async pollUnreadMessages() {
      try {
        const unread = await getUnreadTaskMessages();
        const previousUnread = this.unreadTotal;
        this.unreadTotal = unread.total_unread || 0;

        if (this.unreadTotal > previousUnread) {
          notify(this, {
            type: "info",
            message: `You have ${this.unreadTotal} unread task message(s).`,
            icon: "tim-icons icon-bell-55",
            timeout: 4000,
          });
        }

        if (Array.isArray(unread.tasks) && unread.tasks.length) {
          await Promise.all(
            unread.tasks.slice(0, 5).map((taskMessage) =>
              markTaskMessagesRead(taskMessage.task_id)
            )
          );
        }
      } catch (error) {
      }
    },
    isOwnComment(comment) {
      if (!this.currentUser?.id) {
        return false;
      }

      return comment?.user?.id === this.currentUser.id;
    },
    commentAuthorName(comment) {
      return commentAuthorName(comment);
    },
    commentInitial(comment) {
      return commentInitial(comment);
    },
    commentAvatarStyle(comment) {
      return commentAvatarStyle(comment);
    },
    formatCommentTime(comment) {
      return formatCommentTime(comment);
    },
    replyDraftKey(taskId, commentId) {
      return `${taskId}:${commentId}`;
    },
    replyInputRef(taskId, commentId) {
      return `reply-input-${taskId}-${commentId}`;
    },
    toggleReplyBox(taskId, commentId) {
      const current = this.activeReplyTo[taskId] || null;
      this.$set(this.activeReplyTo, taskId, current === commentId ? null : commentId);

      if (current === commentId) {
        return;
      }

      this.$nextTick(() => {
        const refKey = this.replyInputRef(taskId, commentId);
        const refTarget = this.$refs[refKey];
        const inputComponent = Array.isArray(refTarget) ? refTarget[0] : refTarget;
        const inputElement = inputComponent?.$el?.querySelector("input, textarea");

        if (inputElement) {
          inputElement.focus();
        }
      });
    },
  },
};
</script>

<style scoped>
.task-table-wrap {
  width: 100%;
  overflow-x: auto;
}

.task-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.task-table thead th {
  padding: 0.8rem 0.75rem;
  font-size: 0.72rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.62);
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.02);
  text-align: left;
}

.task-header-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 0;
  background: transparent;
  color: inherit;
  font-size: inherit;
  font-weight: 700;
  letter-spacing: inherit;
  text-transform: inherit;
  padding: 0;
  cursor: pointer;
}

.task-sort-indicator {
  display: inline-block;
  width: 0.75rem;
  text-align: center;
  color: rgba(255, 255, 255, 0.55);
}

.task-table-row {
  cursor: pointer;
  transition: background-color 0.18s ease, box-shadow 0.18s ease;
}

.task-table-row td {
  padding: 0.95rem 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.88);
  font-size: 0.84rem;
}

.task-table-row:hover {
  background: rgba(29, 140, 248, 0.1);
}

.task-col-title {
  width: 34%;
}

.task-title-lg {
  font-size: 1rem;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.95);
  line-height: 1.35;
}

.task-col-actions {
  width: 1%;
  white-space: nowrap;
}

.task-compact-actions {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

@media (max-width: 991px) {
  .task-table thead th,
  .task-table-row td {
    padding: 0.72rem 0.58rem;
  }

  .task-title-lg {
    font-size: 0.92rem;
  }
}

.task-meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}

.task-meta-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.22rem 0.62rem;
  font-size: 0.7rem;
  color: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.05);
}

.task-readonly-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.18rem 0.62rem;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  color: rgba(255, 255, 255, 0.88);
  border: 1px solid rgba(255, 255, 255, 0.25);
  background: rgba(255, 255, 255, 0.08);
}

.task-header-meta {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-wrap: wrap;
}

.comment-thread {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.comment-node {
  display: flex;
  flex-direction: column;
}

.comment-item {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
}

.comment-reply {
  margin-left: 2rem;
  margin-top: 0.45rem;
}

.comment-item.is-own-comment {
  flex-direction: row-reverse;
}

.comment-body-wrap {
  max-width: min(100%, 640px);
}

.comment-item.is-own-comment .comment-body-wrap {
  text-align: right;
}

.comment-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.2rem;
  line-height: 1;
}

.comment-item.is-own-comment .comment-meta {
  justify-content: flex-end;
}

.comment-author {
  font-size: 0.72rem;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.85);
}

.comment-time {
  font-size: 0.66rem;
  color: rgba(255, 255, 255, 0.45);
}

.comment-avatar {
  width: 28px;
  height: 28px;
  min-width: 28px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.06);
}

.comment-bubble {
  display: inline-block;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  padding: 0.45rem 0.7rem;
  color: rgba(255, 255, 255, 0.86);
  font-size: 0.82rem;
  line-height: 1.45;
}

.comment-item.is-own-comment .comment-bubble {
  background: rgba(29, 140, 248, 0.16);
  border-color: rgba(29, 140, 248, 0.35);
}

.comment-actions {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  margin-top: 0.2rem;
}

.comment-action-btn {
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0;
  cursor: pointer;
}

.comment-action-btn:hover {
  color: rgba(255, 255, 255, 0.9);
}

</style>