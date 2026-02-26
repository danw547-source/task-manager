<template>
  <modal
    :show.sync="internalShow"
    type="notice"
    :show-close="true"
    :centered="true"
    modal-content-classes="bg-dark"
    @update:show="onShowUpdate"
  >
    <template slot="header">
      <h4 class="modal-title text-white mb-0">{{ title }}</h4>
    </template>

    <p class="text-muted mb-0">{{ message }}</p>

    <template slot="footer">
      <base-button
        type="default"
        class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
        @click="close"
      >
        {{ cancelText }}
      </base-button>
      <base-button
        :type="confirmType"
        class="text-white bg-danger box-border border border-transparent hover:bg-danger-strong focus:ring-4 focus:ring-danger-medium shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
        @click="confirm"
      >
        {{ confirmText }}
      </base-button>
    </template>
  </modal>
</template>

<script>
import Modal from "@/components/Modal";

export default {
  name: "confirm-dialog",
  components: {
    Modal,
  },
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: "Confirm",
    },
    message: {
      type: String,
      default: "Are you sure?",
    },
    cancelText: {
      type: String,
      default: "Cancel",
    },
    confirmText: {
      type: String,
      default: "Confirm",
    },
    confirmType: {
      type: String,
      default: "danger",
    },
  },
  computed: {
    internalShow: {
      get() {
        return this.show;
      },
      set(value) {
        this.onShowUpdate(value);
      },
    },
  },
  methods: {
    onShowUpdate(value) {
      this.$emit("update:show", value);

      if (!value) {
        this.$emit("cancel");
      }
    },
    close() {
      this.onShowUpdate(false);
    },
    confirm() {
      this.$emit("confirm");
    },
  },
};
</script>
