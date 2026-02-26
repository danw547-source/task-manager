<template>
  <div v-if="isAdmin" class="user-filter-select">
    <select
      v-model.number="internalValue"
      class="form-control form-control-sm"
      @change="emitSelection"
    >
      <option :value="0">{{ allLabel }}</option>
      <option
        v-for="user in users"
        :key="user.id"
        :value="user.id"
      >
        {{ user.name }}
      </option>
    </select>
  </div>
</template>

<script>
import { getUsers } from "@/services/userService";

export default {
  name: "user-filter-select",
  props: {
    value: {
      type: Number,
      default: 0,
    },
    isAdmin: {
      type: Boolean,
      default: false,
    },
    allLabel: {
      type: String,
      default: "All users",
    },
    storageKey: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      users: [],
      internalValue: 0,
    };
  },
  watch: {
    value: {
      immediate: true,
      handler(next) {
        this.internalValue = Number(next || 0);
      },
    },
    isAdmin: {
      immediate: true,
      handler(next) {
        if (next) {
          this.loadUsers();
          return;
        }

        if (!next && this.storageKey) {
          localStorage.removeItem(this.storageKey);
        }
      },
    },
  },
  async mounted() {
    await this.loadUsers();
  },
  methods: {
    async loadUsers() {
      if (!this.isAdmin) {
        this.users = [];
        this.internalValue = 0;
        this.emitSelection();
        this.$emit("users-loaded", []);
        return;
      }

      try {
        const rawUsers = await getUsers();
        this.users = rawUsers
          .map((user) => ({
            id: Number(user.id),
            name: user.name || user.email || `User ${user.id}`,
          }))
          .sort((first, second) => first.name.localeCompare(second.name));

        if (this.storageKey) {
          const storedId = Number(localStorage.getItem(this.storageKey) || 0);
          const hasStored = storedId > 0 && this.users.some((user) => user.id === storedId);
          this.internalValue = hasStored ? storedId : this.internalValue;
        }

        const hasCurrent =
          this.internalValue === 0 || this.users.some((user) => user.id === this.internalValue);

        if (!hasCurrent) {
          this.internalValue = 0;
        }

        this.$emit("users-loaded", this.users);
        this.emitSelection();
      } catch (error) {
        this.users = [];
        this.internalValue = 0;
        this.$emit("users-loaded", []);
        this.emitSelection();
      }
    },
    emitSelection() {
      const nextValue = Number(this.internalValue || 0);

      if (this.storageKey && this.isAdmin) {
        localStorage.setItem(this.storageKey, String(nextValue));
      }

      this.$emit("input", nextValue);
      this.$emit("change", nextValue);
    },
  },
};
</script>

<style scoped>
.user-filter-select {
  width: 220px;
  margin-left: auto;
}

.user-filter-select .form-control {
  height: 30px;
  padding: 0.2rem 0.65rem;
  color: rgba(255, 255, 255, 0.9);
  border-color: rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.05);
}

.user-filter-select .form-control option {
  color: #1e1e2f;
  background: #ffffff;
}
</style>
