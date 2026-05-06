<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->string('workflow_name');
            $table->string('status', 50);
            $table->string('branch');
            $table->integer('duration')->nullable();
            $table->timestamp('run_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipelines');
    }
};
