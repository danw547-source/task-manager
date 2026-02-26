<template>
  <div class="auth-page d-flex align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
          <card class="mb-0">
            <template slot="header">
              <div class="text-center py-2">
                <div class="icon icon-shape icon-lg rounded-circle bg-primary text-white mb-3">
                  <i class="tim-icons icon-single-02"></i>
                </div>
                <h3 class="card-title mb-1">{{ title }}</h3>
                <p class="text-muted mb-0">{{ subtitle }}</p>
              </div>
            </template>

            <div class="mb-3 text-danger text-center" v-if="errorMessage">{{ errorMessage }}</div>

            <slot />

            <div slot="footer" class="pt-2">
              <slot name="footer" />
            </div>
          </card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "auth-card-layout",
  props: {
    title: {
      type: String,
      required: true,
    },
    subtitle: {
      type: String,
      required: true,
    },
    errorMessage: {
      type: String,
      default: "",
    },
  },
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
}
</style>
