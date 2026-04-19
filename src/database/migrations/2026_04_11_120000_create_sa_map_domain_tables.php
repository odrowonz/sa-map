<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('sa_users')->cascadeOnDelete();
            $table->string('name', 500);
            $table->string('slug', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('sa_elements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('project_id')->constrained('sa_projects')->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('artifact_key', 120);
            $table->integer('sort_order')->default(0);
            $table->json('content');
            $table->boolean('include_in_export')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'level', 'artifact_key']);
        });

        Schema::create('sa_element_upstream', function (Blueprint $table) {
            $table->id();
            $table->uuid('element_id');
            $table->uuid('upstream_element_id');
            $table->unique(['element_id', 'upstream_element_id']);
            $table->foreign('element_id')->references('id')->on('sa_elements')->cascadeOnDelete();
            $table->foreign('upstream_element_id')->references('id')->on('sa_elements')->restrictOnDelete();
            $table->index('upstream_element_id');
        });

        Schema::create('sa_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('sa_projects')->cascadeOnDelete();
            $table->string('storage_key', 512);
            $table->string('original_name', 500);
            $table->string('mime_type', 127)->nullable();
            $table->enum('kind', ['scheme', 'png', 'document', 'other']);
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sa_attachment_element', function (Blueprint $table) {
            $table->foreignId('attachment_id')->constrained('sa_attachments')->cascadeOnDelete();
            $table->uuid('element_id');
            $table->primary(['attachment_id', 'element_id']);
            $table->foreign('element_id')->references('id')->on('sa_elements')->cascadeOnDelete();
        });

        Schema::create('sa_njk_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('sa_users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('filename', 255);
            $table->mediumText('body');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_njk_templates');
        Schema::dropIfExists('sa_attachment_element');
        Schema::dropIfExists('sa_attachments');
        Schema::dropIfExists('sa_element_upstream');
        Schema::dropIfExists('sa_elements');
        Schema::dropIfExists('sa_projects');
    }
};
