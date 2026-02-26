<template>
  <auth-card-layout
    title="Welcome Back"
    subtitle="Sign in to continue to your dashboard"
    :error-message="errorMessage"
  >
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
      @keyup.enter="onSubmit"
    />

    <template slot="footer">
      <base-button type="primary" class="w-100 mb-3" :disabled="loading" @click="onSubmit">
        {{ loading ? "Signing in..." : "Sign In" }}
      </base-button>
      <div class="text-center">
        <router-link to="/register">Create account</router-link>
      </div>
    </template>
  </auth-card-layout>
</template>

<script>
import { login } from "@/services/authService";
import AuthCardLayout from "@/components/Auth/AuthCardLayout.vue";

export default {
  name: "login-page",
  components: {
    AuthCardLayout,
  },
  data() {
    return {
      loading: false,
      errorMessage: "",
      form: {
        email: "",
        password: "",
      },
    };
  },
  methods: {
    async onSubmit() {
      this.errorMessage = "";
      this.loading = true;

      try {
        await login(this.form);
        this.$router.push({ name: "home" });
      } catch (error) {
        this.errorMessage = error.message || "Unable to sign in.";
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
