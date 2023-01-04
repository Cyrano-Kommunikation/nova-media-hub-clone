<?php

use Cyrano\MediaHub\MediaHub;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        /**
         * Create collection table
         */
        Schema::create(MediaHub::getCollectionTableName(), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        /**
         * Create media hub table.
         */
        Schema::create(MediaHub::getTableName(), function (Blueprint $table) {
            // Primary keys
            $table->bigIncrements('id');
            $table->uuid('uuid')->nullable()->unique();

            $table->unsignedBigInteger('collection_id');

            // File info
            $table->string('disk');
            $table->string('file_name');
            $table->unsignedBigInteger('size');
            $table->string('mime_type')->nullable();

            // Save original file hash to check for duplicate uploads
            $table->string('original_file_hash');

            // Data
            $table->json('data');

            // Conversions
            $table->json('conversions');
            $table->string('conversions_disk')->nullable();

            $table->timestamps();
            $table->timestamp('optimized_at')->nullable();

            $table->foreign('collection_id')->references('id')->on(MediaHub::getCollectionTableName());
        });

        Schema::create(MediaHub::getTagTableName(), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create(MediaHub::getTaggableTableName(), function (Blueprint $table) {
            $table->bigIncrements('tag_id')->primary();
            $table->integer('taggable_id');
            $table->string('taggable_type');

            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(MediaHub::getTableName());
        Schema::dropIfExists(MediaHub::getCollectionTableName());
        Schema::dropIfExists(MediaHub::getTagTableName());
        Schema::dropIfExists(MediaHub::getTaggableTableName());
    }
};
