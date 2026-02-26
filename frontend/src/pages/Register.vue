<template>
  <auth-card-layout
    title="Create Account"
    subtitle="Get started with your task dashboard"
    :error-message="errorMessage"
  >
    <base-input
      label="Name"
      type="text"
      placeholder="Your name"
      v-model="form.name"
    />

    <base-input
      label="Email"
      type="email"
      placeholder="you@example.com"
      v-model="form.email"
    />

    <base-input
      label="Password"
      type="password"
      placeholder="********"
      v-model="form.password"
    />

    <base-input
      label="Confirm Password"
      type="password"
      placeholder="********"
      v-model="form.password_confirmation"
      @keyup.enter="onSubmit"
    />

    <template slot="footer">
      <base-button type="primary" class="w-100 mb-3" :disabled="loading" @click="onSubmit">
        {{ loading ? "Creating..." : "Create Account" }}
      </base-button>
      <div class="text-center">
        <router-link to="/login">Already have an account?</router-link>
      </div>
    </template>
  </auth-card-layout>
</template>

<script>
import { register } from "@/services/authService";
import AuthCardLayout from "@/components/Auth/AuthCardLayout.vue";

export default {
  name: "register-page",
  components: {
    AuthCardLayout,
  },
  data() {
    return {
      loading: false,
      errorMessage: "",
      form: {
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
      },
    };
  },
  methods: {
    async onSubmit() {
      this.errorMessage = "";
      this.loading = true;

      try {
        await register(this.form);
        this.$router.push({ name: "tasks" });
      } catch (error) {
        this.errorMessage = error.message || "Unable to create account.";
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
