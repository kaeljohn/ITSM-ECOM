<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ecommerce';

    public function up(): void
    {
        Schema::connection('ecommerce')->create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id'); // the CUSTOMER
            $table->string('sender_type'); // 'admin' or 'customer'
            $table->unsignedBigInteger('sender_id')->nullable(); // admin ID if admin sent
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'user_id', 'created_at'], 'chat_conversation_idx');
            $table->index(['client_id', 'user_id', 'is_read'], 'chat_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('chat_messages');
    }
};
