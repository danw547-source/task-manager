import DashboardLayout from "@/layout/dashboard/DashboardLayout.vue";

// Admin pages
const Dashboard = () =>
  import(/* webpackChunkName: "dashboard" */ "@/pages/Dashboard.vue");
const Tasks = () =>
  import(/* webpackChunkName: "common" */ "@/pages/Tasks.vue");
const Login = () =>
  import(/* webpackChunkName: "auth" */ "@/pages/Login.vue");
const Register = () =>
  import(/* webpackChunkName: "auth" */ "@/pages/Register.vue");

const routes = [
  {
    path: "/login",
    name: "login",
    component: Login,
    meta: { publicOnly: true },
  },
  {
    path: "/register",
    name: "register",
    component: Register,
    meta: { publicOnly: true },
  },
  {
    path: "/",
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "home",
        component: Tasks,
      },
      {
        path: "dashboard",
        name: "dashboard",
        component: Dashboard,
      },
    ],
  },
  { path: "*", redirect: "/" },
];

/**
 * Asynchronously load view (Webpack Lazy loading compatible)
 * The specified component must be inside the Views folder
 * @param  {string} name  the filename (basename) of the view to load.
function view(name) {
   var res= require('../components/Dashboard/Views/' + name + '.vue');
   return res;
};**/

export default routes;
