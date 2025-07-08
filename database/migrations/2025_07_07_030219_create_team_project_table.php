<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_project', function (Blueprint $table) {
            $table->foreignUuid('team_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('projects_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->primary(['team_id', 'projects_id']); // optional, but good practice
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_project');
    }
};
