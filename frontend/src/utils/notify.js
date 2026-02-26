function notify(vm, { type = "info", message = "", timeout = 2500, icon } = {}) {
  if (!vm?.$notify || !message) {
    return;
  }

  vm.$notify({
    type,
    message,
    timeout,
    ...(icon ? { icon } : {}),
  });
}

export { notify };
