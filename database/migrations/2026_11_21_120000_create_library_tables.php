<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->string('isbn')->nullable()->unique();
            $table->foreignId('category_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('publisher_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('shelf_id')->index()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->text('synopsis');
            $table->string('cover');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('author_book', function (Blueprint $table) {
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->primary(['author_id', 'book_id']);
            $table->index('book_id');
        });

        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->index()->constrained()->restrictOnDelete();
            $table->string('barcode')->unique();
            $table->enum('status', ['available', 'borrowed', 'damaged', 'lost'])->default('available')->index();
            $table->string('condition')->default('good');
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('membership_number')->unique();
            $table->string('name')->index();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('joined_at')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active')->index();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('book_copy_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->index()->constrained()->restrictOnDelete();
            $table->date('loan_date')->index();
            $table->date('due_date')->index();
            $table->date('return_date')->nullable();
            $table->unsignedTinyInteger('extension_count')->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });

        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->index()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('days_late');
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->index()->constrained()->cascadeOnDelete();
            $table->timestamp('reserved_at')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['book_id', 'status']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->nullableMorphs('subject');
            $table->string('action')->index();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('fines');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('members');
        Schema::dropIfExists('book_copies');
        Schema::dropIfExists('author_book');
        Schema::dropIfExists('books');
        Schema::dropIfExists('shelves');
        Schema::dropIfExists('publishers');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('categories');
    }
};
