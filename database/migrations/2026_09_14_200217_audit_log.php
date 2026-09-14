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
         Schema::create('tbl_audit_logs', function (Blueprint $table) {
            $table->id();

            // Polymorphic subject
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');

            // Actor
            $table->nullableMorphs('actor'); // actor_type + actor_id (User, System, etc.)

            // Event
            $table->string('event', 30); // created, updated, deleted, restored, paid, refunded, cancelled
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changed_fields')->nullable();

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('tags')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Query-friendly indexes
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audit_subject_idx');
            $table->index(['event', 'created_at'], 'audit_event_idx');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('tbl_audit_logs');
    }
};
