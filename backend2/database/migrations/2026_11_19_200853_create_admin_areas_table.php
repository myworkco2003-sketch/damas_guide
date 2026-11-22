<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 150)->index();
            $table->string('name_en', 150)->default('');
            $table->string('code', 50);
            $table->string('pcode', 50)->nullable();
            $table->integer('level')->index();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('admin_areas')
                ->cascadeOnDelete();
            $table->jsonb('boundary');
            $table->string('search_text', 500)->default('')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index(['level', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_areas');
    }
};
