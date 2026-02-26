// Transforms a flat comment list into a parent/reply tree for rendering.
export function buildCommentTree(comments = []) {
  const sorted = [...comments].sort((first, second) => first.id - second.id);
  const byId = new Map();

  sorted.forEach((comment) => {
    byId.set(comment.id, {
      ...comment,
      replies: [],
    });
  });

  const roots = [];

  byId.forEach((comment) => {
    if (comment.parent_comment_id && byId.has(comment.parent_comment_id)) {
      byId.get(comment.parent_comment_id).replies.push(comment);
    } else {
      roots.push(comment);
    }
  });

  return roots;
}

// Display helpers below keep presentation logic out of the page component.
export function commentAuthorName(comment) {
  return comment?.user?.name || "User";
}

export function commentInitial(comment) {
  const name = commentAuthorName(comment).trim();

  return name ? name.charAt(0).toUpperCase() : "U";
}

export function commentAvatarStyle(comment) {
  const seed = commentAuthorName(comment);
  // Small deterministic palette gives each author a stable avatar color.
  const palette = ["#1d8cf8", "#e14eca", "#00f2c3", "#ff8d72", "#fd5d93", "#11cdef"];

  const index = [...seed].reduce((acc, char) => acc + char.charCodeAt(0), 0) % palette.length;

  return {
    backgroundColor: palette[index],
  };
}

export function formatCommentTime(comment) {
  if (!comment?.created_at) {
    return "Now";
  }

  try {
    return new Date(comment.created_at).toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch (error) {
    return "Now";
  }
}
