<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->foreignId('parent_comment_id')
                ->nullable()
                ->after('user_id')
                ->constrained('task_comments')
                ->nullOnDelete();

            $table->index(['task_id', 'parent_comment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_comment_id']);
            $table->dropIndex(['task_id', 'parent_comment_id', 'created_at']);
            $table->dropColumn('parent_comment_id');
        });
    }
};
