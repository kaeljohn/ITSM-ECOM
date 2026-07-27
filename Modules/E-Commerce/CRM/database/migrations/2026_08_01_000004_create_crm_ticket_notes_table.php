<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->create('crm_ticket_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Ticket relationship (cascade delete removes notes when ticket is deleted)
            $table->unsignedBigInteger('ticket_id')->index();
            $table->foreign('ticket_id')
                ->references('id')
                ->on('crm_tickets')
                ->cascadeOnDelete();

            // Author info (denormalized for fast display, avoids join to staff table)
            $table->unsignedBigInteger('author_id')->nullable()
                ->comment('User ID from the staff/admin system');
            $table->string('author_name', 150)->nullable()
                ->comment('Denormalized display name');

            // The note body (can be rich text / HTML)
            $table->text('body');

            // @mention support — stores array of mentioned user identifiers
            $table->jsonb('mentions')->nullable()
                ->comment('[{"type":"staff","id":1,"name":"Jane"},{"type":"staff","id":2,"name":"John"}]');

            // Visibility — internal vs customer-facing
            $table->boolean('is_internal')->default(true)
                ->comment('true = staff-only, false = visible on customer portal');

            // Optional: type classification for rendering
            $table->string('note_type', 30)->default('note')
                ->comment('note, reply, internal_note, system_action');

            $table->timestamps();

            // Performance indexes
            $table->index(['client_id', 'ticket_id', 'created_at'], 'crm_tnote_ticket_created_idx');
            $table->index(['client_id', 'author_id'], 'crm_tnote_author_idx');
            $table->index(['client_id', 'is_internal'], 'crm_tnote_visibility_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('crm_ticket_notes');
    }
};
