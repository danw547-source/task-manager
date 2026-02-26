function getTodayDateString() {
  const today = new Date();
  const year = today.getFullYear();
  const month = `${today.getMonth() + 1}`.padStart(2, "0");
  const day = `${today.getDate()}`.padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function normalizeDueDateForInput(value) {
  if (!value) {
    return "";
  }

  const stringValue = String(value);
  const leadingIsoDate = stringValue.match(/^(\d{4}-\d{2}-\d{2})/);

  if (leadingIsoDate) {
    return leadingIsoDate[1];
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return "";
  }

  const year = parsed.getFullYear();
  const month = String(parsed.getMonth() + 1).padStart(2, "0");
  const day = String(parsed.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function formatDateForDisplay(value, fallback = "N/A") {
  if (!value) {
    return fallback;
  }

  try {
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      return fallback;
    }

    return new Intl.DateTimeFormat("en-GB", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    }).format(parsed);
  } catch (error) {
    return fallback;
  }
}

function validateTaskForm(
  taskForm,
  todayDateString = getTodayDateString(),
  options = {}
) {
  const { requireDueDate = true, includeDueDateInPayload = true } = options;
  const title = (taskForm?.title || "").trim();
  const description = (taskForm?.description || "").trim();
  const dueDate = taskForm?.due_date || "";

  if (!title) {
    return { valid: false, error: "Task title is required." };
  }

  if (!description) {
    return { valid: false, error: "Task description is required." };
  }

  if (requireDueDate) {
    if (!dueDate) {
      return { valid: false, error: "Task due date is required." };
    }

    if (dueDate < todayDateString) {
      return { valid: false, error: "Task due date cannot be in the past." };
    }
  }

  const payload = {
    title,
    description,
  };

  if (includeDueDateInPayload && dueDate) {
    payload.due_date = dueDate;
  }

  return {
    valid: true,
    error: "",
    payload,
  };
}

export {
  getTodayDateString,
  normalizeDueDateForInput,
  formatDateForDisplay,
  validateTaskForm,
};
