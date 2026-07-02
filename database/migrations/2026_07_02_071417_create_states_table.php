<?php

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
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color');
            $table->unsignedInteger('sequence');
            $table->string('group');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
            $table->unique(['project_id', 'slug']);
            $table->index(['project_id', 'sequence']);
            $table->index(['project_id', 'group']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('default_state_id')->references('id')->on('states')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['default_state_id']);
        });

        Schema::dropIfExists('states');
    }
};
