<template>
  <div class="sidebar" :data="backgroundColor">
    <!--
            Tip 1: you can change the color of the sidebar's background using: data-background-color="white | black | darkblue"
            Tip 2: you can change the color of the active button using the data-active-color="primary | info | success | warning | danger"
        -->
    <!-- -->
    <div class="sidebar-wrapper flex h-full flex-col gap-3 px-3 py-4" id="style-3">
      <div v-if="currentUser" class="sidebar-notifications rounded-xl border border-white/15 bg-white/5 px-2 py-2 backdrop-blur-sm">
        <base-dropdown
          tag="div"
          title-tag="button"
          class="sidebar-notifications-dropdown"
        >
          <template slot="title">
            <div class="sidebar-notifications-trigger flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-slate-100/95 transition-colors duration-150 hover:bg-white/10">
              <div class="sidebar-bell-wrap relative inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                <i class="tim-icons icon-bell-55"></i>
                <span v-if="unreadSummary.total_unread > 0" class="sidebar-bell-badge">
                  {{ unreadSummary.total_unread > 99 ? "99+" : unreadSummary.total_unread }}
                </span>
              </div>
              <span class="sidebar-notifications-label text-xs font-semibold uppercase tracking-wide">Updates</span>
              <i class="tim-icons icon-minimal-down sidebar-user-caret"></i>
            </div>
          </template>

          <li v-if="!unreadSummary.total_unread" class="nav-link">
            <span class="nav-item dropdown-item disabled">No new updates</span>
          </li>

          <li
            v-for="entry in unreadSummary.tasks"
            :key="entry.task_id"
            class="nav-link"
          >
            <a href="#" class="nav-item dropdown-item" @click.prevent="openTaskUpdates(entry.task_id)">
              Task #{{ entry.task_id }} · {{ entry.unread_count }} new
            </a>
          </li>
        </base-dropdown>
      </div>

      <div v-if="currentUser" class="sidebar-user rounded-xl border border-white/15 bg-white/5 px-2 py-2 backdrop-blur-sm">
        <base-dropdown
          tag="div"
          title-tag="button"
          class="sidebar-user-dropdown"
        >
          <template slot="title">
            <div class="sidebar-user-trigger flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-slate-100/95 transition-colors duration-150 hover:bg-white/10">
              <div class="sidebar-user-photo" :style="userAvatarStyle">
                {{ userInitial }}
              </div>
              <div class="sidebar-user-meta min-w-0">
                <span class="sidebar-user-name block truncate text-sm font-semibold text-white">{{ userDisplayName }}</span>
                <small v-if="currentUser?.role" class="sidebar-user-role block text-[10px] uppercase tracking-wide text-white/70">{{ currentUser.role }}</small>
              </div>
              <i class="tim-icons icon-minimal-down sidebar-user-caret"></i>
            </div>
          </template>

          <li class="nav-link">
            <span class="nav-item dropdown-item disabled">Signed in as {{ userDisplayName }}</span>
          </li>
          <div class="dropdown-divider"></div>
          <li class="nav-link">
            <a href="#" class="nav-item dropdown-item" @click.prevent="onLogout">Log out</a>
          </li>
        </base-dropdown>
      </div>
    </div>
  </div>
</template>
<script>
import { fetchCurrentUser, getStoredUser, logout } from "@/services/authService";
import { getUnreadTaskMessages, markTaskMessagesRead } from "@/services/taskService";
import appConfig from "@/config";

export default {
  props: {
    backgroundColor: {
      type: String,
      default: "vue",
    },
    activeColor: {
      type: String,
      default: "success",
      validator: (value) => {
        let acceptedValues = [
          "primary",
          "info",
          "success",
          "warning",
          "danger",
        ];
        return acceptedValues.indexOf(value) !== -1;
      },
    },
  },
  computed: {
    userDisplayName() {
      return this.currentUser?.name || this.currentUser?.email || "Account";
    },
    userInitial() {
      const base = (this.currentUser?.name || this.currentUser?.email || "A").trim();
      return base.charAt(0).toUpperCase();
    },
    userAvatarStyle() {
      const seed = (this.currentUser?.name || this.currentUser?.email || "account").trim();
      const palette = [
        appConfig?.colors?.primary,
        appConfig?.colors?.info,
        appConfig?.colors?.danger,
        appConfig?.colors?.teal,
        appConfig?.colors?.default,
      ].filter(Boolean);
      const index = [...seed].reduce((accumulator, char) => accumulator + char.charCodeAt(0), 0) % palette.length;

      return {
        backgroundColor: palette[index],
      };
    },
  },
  data() {
    return {
      currentUser: null,
      unreadSummary: {
        total_unread: 0,
        tasks: [],
      },
      unreadPollTimer: null,
    };
  },
  methods: {
    async loadCurrentUser() {
      this.currentUser = getStoredUser();

      try {
        this.currentUser = await fetchCurrentUser();
      } catch (error) {
      }
    },
    async onLogout() {
      try {
        await logout();
      } finally {
        if (this.unreadPollTimer) {
          clearInterval(this.unreadPollTimer);
        }
        this.currentUser = null;
        this.$router.push({ name: "login" });
      }
    },
    async loadUnreadSummary() {
      if (!this.currentUser) {
        this.unreadSummary = {
          total_unread: 0,
          tasks: [],
        };
        return;
      }

      try {
        const summary = await getUnreadTaskMessages();
        this.unreadSummary = {
          total_unread: Number(summary.total_unread || 0),
          tasks: Array.isArray(summary.tasks) ? summary.tasks : [],
        };
      } catch (error) {
      }
    },
    async openTaskUpdates(taskId) {
      try {
        await markTaskMessagesRead(taskId);
      } catch (error) {
      } finally {
        await this.loadUnreadSummary();
        if (this.$route.path !== "/tasks") {
          this.$router.push({ name: "tasks" });
        }
      }
    },
  },
  async mounted() {
    await this.loadCurrentUser();
    await this.loadUnreadSummary();
    this.unreadPollTimer = setInterval(this.loadUnreadSummary, 20000);
  },
  beforeDestroy() {
    if (this.unreadPollTimer) {
      clearInterval(this.unreadPollTimer);
    }
  },
};
</script>

<style scoped>
.sidebar-bell-wrap i {
  font-size: 0.82rem;
  color: #fff;
}

.sidebar-bell-badge {
  position: absolute;
  top: -6px;
  right: -8px;
  min-width: 16px;
  height: 16px;
  border-radius: 999px;
  padding: 0 4px;
  font-size: 0.62rem;
  font-weight: 700;
  line-height: 16px;
  text-align: center;
  color: #fff;
  background: #fd5d93;
}

.sidebar-user {
  margin-bottom: 0.25rem;
  position: relative;
  z-index: 10;
}

.sidebar-user-photo {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  flex-shrink: 0;
}

.sidebar-user-caret {
  margin-left: auto;
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.72);
}

.sidebar-user-dropdown ::v-deep .dropdown-menu {
  min-width: 220px;
  z-index: 3000;
  left: 0 !important;
  right: auto !important;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.75rem;
  background: rgba(24, 31, 54, 0.96);
  backdrop-filter: blur(8px);
  padding: 0.35rem;
}

.sidebar-notifications {
  position: relative;
  z-index: 40;
}

.sidebar-notifications-dropdown {
  position: relative;
  z-index: 4000;
}

.sidebar-notifications-dropdown.show {
  z-index: 4100;
}

.sidebar-user-dropdown {
  position: relative;
  z-index: 2000;
}

.sidebar-user-dropdown.show {
  z-index: 2100;
}

.sidebar-notifications-dropdown ::v-deep .dropdown-menu {
  min-width: 220px;
  z-index: 4200;
  left: 0 !important;
  right: auto !important;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.75rem;
  background: rgba(24, 31, 54, 0.96);
  backdrop-filter: blur(8px);
  padding: 0.35rem;
}

.sidebar-user-dropdown ::v-deep .dropdown-item,
.sidebar-notifications-dropdown ::v-deep .dropdown-item {
  border-radius: 0.55rem;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.78rem;
}

.sidebar-user-dropdown ::v-deep .dropdown-item:hover,
.sidebar-notifications-dropdown ::v-deep .dropdown-item:hover {
  background: rgba(255, 255, 255, 0.12);
}
</style>
