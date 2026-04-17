<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekrutmen_request_man_power_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_man_power_id')
                ->constrained(
                    table: 'rekrutmen_request_man_powers',
                    indexName: 'rekrutmen_rmp_approvals_request_fk',
                )
                ->cascadeOnDelete();
            $table->foreignId('approver_id')
                ->nullable()
                ->constrained(
                    table: 'rekrutmen_approvers',
                    indexName: 'rekrutmen_rmp_approvals_approver_fk',
                )
                ->nullOnDelete();
            $table->string('approver_name');
            $table->string('approver_email')->nullable();
            $table->string('approver_title')->nullable();
            $table->unsignedInteger('step_order');
            $table->string('status')->default('waiting');
            $table->string('action_token')->nullable();
            $table->dateTime('action_expires_at')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('acted_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('acted_by_user_id')
                ->nullable()
                ->constrained(
                    table: 'users',
                    indexName: 'rekrutmen_rmp_approvals_actor_fk',
                )
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['request_man_power_id', 'step_order'], 'rekrutmen_request_man_power_approvals_step_index');
            $table->index(['request_man_power_id', 'status'], 'rekrutmen_request_man_power_approvals_status_index');
            $table->unique(['request_man_power_id', 'step_order'], 'rekrutmen_request_man_power_approvals_unique_step');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekrutmen_request_man_power_approvals');
    }
};
