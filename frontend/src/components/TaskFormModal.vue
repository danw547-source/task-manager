<template>
  <modal
    :show.sync="internalShow"
    type="notice"
    :show-close="true"
    :centered="true"
    modal-classes="modal-2x"
    modal-content-classes="bg-dark"
    @update:show="onShowUpdate"
  >
    <template slot="header">
      <h4 class="modal-title text-white mb-0">{{ title }}</h4>
    </template>

    <div class="row">
      <div class="col-md-8">
        <base-input
          label="Title"
          type="text"
          placeholder="Task title"
          v-model="localForm.title"
          required
          @input="emitForm"
        />
      </div>
      <div class="col-md-4">
        <base-input
          label="Due Date"
          :type="dueDateInputType"
          v-model="localForm.due_date"
          :min="minDate"
          :disabled="dueDateDisabled"
          :readonly="dueDateReadonly"
          required
          @input="emitForm"
        />
      </div>
    </div>

    <base-input
      label="Description"
      type="textarea"
      placeholder="Task description"
      v-model="localForm.description"
      required
      @input="emitForm"
    />

    <p v-if="errorMessage" class="text-danger mb-0">{{ errorMessage }}</p>

    <template slot="footer">
      <base-button
        type="default"
        class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
        @click="close"
      >
        {{ cancelText }}
      </base-button>
      <base-button
        type="default"
        class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-full text-sm px-4 py-2.5 focus:outline-none"
        :disabled="submitting"
        @click="submit"
      >
        {{ submitting ? submittingText : submitText }}
      </base-button>
    </template>
  </modal>
</template>

<script>
import Modal from "@/components/Modal";

export default {
  name: "task-form-modal",
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
      default: "Task",
    },
    submitText: {
      type: String,
      default: "Save",
    },
    submittingText: {
      type: String,
      default: "Saving...",
    },
    cancelText: {
      type: String,
      default: "Cancel",
    },
    submitting: {
      type: Boolean,
      default: false,
    },
    minDate: {
      type: String,
      default: "",
    },
    dueDateDisabled: {
      type: Boolean,
      default: false,
    },
    dueDateReadonly: {
      type: Boolean,
      default: false,
    },
    dueDateInputType: {
      type: String,
      default: "date",
    },
    errorMessage: {
      type: String,
      default: "",
    },
    formData: {
      type: Object,
      default: () => ({
        title: "",
        description: "",
        due_date: "",
      }),
    },
  },
  data() {
    return {
      localForm: {
        title: "",
        description: "",
        due_date: "",
      },
    };
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
  watch: {
    formData: {
      immediate: true,
      deep: true,
      handler(nextValue) {
        this.localForm = {
          ...nextValue,
        };
      },
    },
  },
  methods: {
    emitForm() {
      this.$emit("update:formData", {
        ...this.localForm,
      });
    },
    onShowUpdate(value) {
      this.$emit("update:show", value);

      if (!value) {
        this.$emit("cancel");
      }
    },
    close() {
      this.onShowUpdate(false);
    },
    submit() {
      const payload = {
        ...this.localForm,
      };

      this.$emit("update:formData", payload);
      this.$emit("submit", payload);
    },
  },
};
</script>
