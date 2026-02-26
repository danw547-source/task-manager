<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }} - Task Manager Blog</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7fb; color: #111827; }
        .container { max-width: 760px; margin: 0 auto; padding: 2rem 1rem; }
        .back { color: #2563eb; text-decoration: none; font-weight: 600; }
        .card { margin-top: 1rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1.25rem; }
        .meta { color: #6b7280; font-size: .9rem; margin-bottom: .8rem; }
        h1 { margin: 0 0 .8rem; font-size: 1.9rem; }
        .status { display: inline-block; font-size: .75rem; padding: .2rem .5rem; border-radius: 999px; background: #eef2ff; color: #3730a3; margin-bottom: 1rem; }
        .content { line-height: 1.65; color: #1f2937; white-space: pre-line; }
    </style>
</head>
<body>
    <div class="container">
        <a class="back" href="{{ route('blog.index') }}">← Back to blog</a>

        <article class="card">
            <div class="meta">
                {{ $post->created_at?->format('M d, Y H:i') }}
                @if($post->user)
                    · {{ $post->user->name }}
                @endif
            </div>
            <h1>{{ $post->title }}</h1>
            <span class="status">{{ str_replace('_', ' ', $post->status) }}</span>
            <div class="content">{{ $post->description ?: 'No content provided for this post.' }}</div>
        </article>
    </div>
</body>
</html>
