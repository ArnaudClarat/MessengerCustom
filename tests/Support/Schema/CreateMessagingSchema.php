<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagingSchema
{
    public static function create(): void
    {
        // Table des utilisateurs.
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->text('password_hash')->nullable();
            $table->text('display_name');
            $table->text('avatar_url')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Table des organisations.
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('name');
            $table->string('slug')->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        // Membres des organisations.
        Schema::create('org_members', function (Blueprint $table) {
            $table->uuid('org_id');
            $table->uuid('user_id');
            $table->string('role')->default('member');
            $table->string('status')->default('active');
            $table->timestamp('joined_at')->useCurrent();

            $table->primary(['org_id', 'user_id']);

            $table->foreign('org_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });

        // Conversations.
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            $table->string('type');
            $table->string('visibility')->default('private');
            $table->text('name')->nullable();
            $table->text('topic')->nullable();
            $table->text('direct_key')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('archived_at')->nullable();

            $table->unique(['org_id', 'direct_key']);

            $table->foreign('org_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });



        // ======================================================
        // TABLE DES MESSAGES
        // ======================================================
        Schema::create('messages', function (Blueprint $table) {

            // Identifiant numérique auto-incrémenté.
            $table->id();

            // Conversation à laquelle appartient le message.
            $table->uuid('conversation_id');

            // Expéditeur du message.
            $table->uuid('sender_id')->nullable();

            // Type de message : user ou system.
            $table->string('type')->default('user');

            // Contenu du message.
            $table->text('content');

            // Référence éventuelle à un autre message.
            $table->unsignedBigInteger('reply_to_id')->nullable();

            // Dates de création, modification et suppression.
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            // Un message appartient à une conversation.
            $table->foreign('conversation_id')
                ->references('id')
                ->on('conversations')
                ->cascadeOnDelete();

            // L'expéditeur peut être supprimé sans supprimer le message.
            $table->foreign('sender_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Un message peut répondre à un autre message.
            $table->foreign('reply_to_id')
                ->references('id')
                ->on('messages')
                ->nullOnDelete();
        });


        // Participants des conversations.
        Schema::create('conversation_members', function (Blueprint $table) {
            $table->uuid('conversation_id');
            $table->uuid('user_id');
            $table->uuid('org_id');
            $table->string('role')->default('member');
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->boolean('muted')->default(false);
            $table->timestamp('joined_at')->useCurrent();

            $table->primary(['conversation_id', 'user_id']);

            $table->foreign('conversation_id')
                ->references('id')->on('conversations')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }
}
