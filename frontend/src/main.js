/*
 =========================================================
 * TaskFlow Dashboard - v1.1.3
 =========================================================

 * Local task management dashboard

 =========================================================

 * Project source header

 */
import Vue from "vue";
import VueRouter from "vue-router";
import RouterPrefetch from "vue-router-prefetch";
import App from "./App";
import router from "./router/index";

import BlackDashboard from "./plugins/blackDashboard";
import i18n from "./i18n";
import "./registerServiceWorker";
import "@/assets/css/tailwind.css";
Vue.use(BlackDashboard);
Vue.use(VueRouter);
Vue.use(RouterPrefetch);
new Vue({
  router,
  i18n,
  render: (h) => h(App),
}).$mount("#app");
