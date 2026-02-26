<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager Blog</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7fb; color: #111827; }
        .container { max-width: 960px; margin: 0 auto; padding: 2rem 1rem; }
        .header { margin-bottom: 1.5rem; }
        .header h1 { margin: 0 0 .5rem; font-size: 2rem; }
        .header p { margin: 0; color: #6b7280; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1rem; }
        .meta { color: #6b7280; font-size: .85rem; margin-bottom: .6rem; }
        .title { margin: 0 0 .5rem; font-size: 1.1rem; }
        .excerpt { color: #374151; line-height: 1.45; margin: 0 0 .8rem; }
        .status { display: inline-block; font-size: .75rem; padding: .2rem .5rem; border-radius: 999px; background: #eef2ff; color: #3730a3; margin-bottom: .8rem; }
        .link { color: #2563eb; text-decoration: none; font-weight: 600; }
        .empty { background: #fff; border: 1px dashed #d1d5db; border-radius: 10px; padding: 2rem; text-align: center; color: #6b7280; }
        .pagination { margin-top: 1.5rem; }
        .pagination nav { display: flex; justify-content: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Blog</h1>
            <p>Simple server-rendered blog page using database records.</p>
        </div>

        @if($posts->count())
            <div class="grid">
                @foreach($posts as $post)
                    <article class="card">
                        <div class="meta">
                            {{ $post->created_at?->format('M d, Y') }}
                            @if($post->user)
                                · {{ $post->user->name }}
                            @endif
                        </div>
                        <h2 class="title">{{ $post->title }}</h2>
                        <span class="status">{{ str_replace('_', ' ', $post->status) }}</span>
                        <p class="excerpt">{{ \Illuminate\Support\Str::limit($post->description ?: 'No content provided.', 120) }}</p>
                        <a class="link" href="{{ route('blog.show', $post) }}">Read post →</a>
                    </article>
                @endforeach
            </div>

            <div class="pagination">
                {{ $posts->links() }}
            </div>
        @else
            <div class="empty">No posts found in the database yet.</div>
        @endif
    </div>
</body>
</html>
