<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('role')->default(0)->index();
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->boolean('email_notifications')->default(true);
            $table->boolean('in_app_notifications')->default(true);
            $table->unsignedSmallInteger('reminder_days')->nullable();
            $table->string('appearance', 10)->default('system');
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 255);
            $table->string('merchant', 255)->nullable();
            $table->decimal('amount', 7, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();
        });
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255)->nullable();
            $table->string('file_name', 255);
            $table->string('file_type', 100);
            $table->unsignedInteger('file_size');
            $table->binary('file_content')->nullable();
            $table->string('kind', 12)->default('other');
            $table->string('merchant', 255)->nullable();
            $table->decimal('amount', 7, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'kind']);
            $table->index(['user_id', 'warranty_end_date']);
            $table->index(['user_id', 'created_at']);
        });
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE documents MODIFY file_content LONGBLOB NULL');
        }
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('message', 500);
            $table->string('kind', 12)->default('warranty');
            $table->unsignedTinyInteger('status')->default(0);
            $table->date('notification_date');
            $table->string('deduplication_key', 150)->nullable()->unique();
            $table->boolean('in_app')->default(true);
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('system_settings');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'status', 'email_notifications', 'in_app_notifications', 'reminder_days', 'appearance']);
        });
    }
};
