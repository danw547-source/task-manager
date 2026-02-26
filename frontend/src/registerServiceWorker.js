/* eslint-disable no-console */

import { register } from "register-service-worker";

const isLocalTestDomain =
  typeof window !== "undefined" &&
  (window.location.hostname === "localhost" ||
    window.location.hostname === "127.0.0.1" ||
    window.location.hostname.endsWith(".test"));

if (process.env.NODE_ENV === "production" && !isLocalTestDomain) {
  register(`${process.env.BASE_URL}service-worker.js`, {
    registrationOptions: {
      scope: process.env.BASE_URL,
    },
    ready() {
      console.log("App is being served from cache by a service worker.");
    },
    registered() {
      console.log("Service worker has been registered.");
    },
    cached() {
      console.log("Content has been cached for offline use.");
    },
    updatefound() {
      console.log("New content is downloading.");
    },
    updated() {
      console.log("New content is available; please refresh.");
    },
    offline() {
      console.log(
        "No internet connection found. App is running in offline mode."
      );
    },
    error(error) {
      console.error("Error during service worker registration:", error);
    },
  });
}
