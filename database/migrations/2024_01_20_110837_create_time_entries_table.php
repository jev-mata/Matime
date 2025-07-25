<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('description', 500);
            $table->dateTime('start');
            $table->dateTime('end')->nullable();
            $table->integer('billable_rate')->unsigned()->nullable();
            $table->boolean('billable')->default(false);
            $table->uuid('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->uuid('organization_id');
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->uuid('project_id')->nullable();
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->uuid('task_id')->nullable();
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->jsonb('tags')->nullable();
            $table->timestamps();

            $table->index('start');
            $table->index('end');
            $table->index('billable');
            $table->string('approval');
            $table->uuid('approved_by');
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
