import VueRouter from "vue-router";
import routes from "./routes";
import { isAuthenticated } from "@/services/authService";

// configure router
const router = new VueRouter({
  mode: "history",
  routes, // short for routes: routes
  linkExactActiveClass: "active",
  scrollBehavior: (to) => {
    if (to.hash) {
      return { selector: to.hash };
    } else {
      return { x: 0, y: 0 };
    }
  },
});

router.beforeEach((to, from, next) => {
  const authed = isAuthenticated();
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
  const publicOnly = to.matched.some((record) => record.meta.publicOnly);

  if (requiresAuth && !authed) {
    return next({ name: "login" });
  }

  if (publicOnly && authed) {
    return next({ name: "home" });
  }

  return next();
});

export default router;
