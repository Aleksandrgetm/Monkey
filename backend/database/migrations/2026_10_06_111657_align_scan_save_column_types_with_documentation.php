<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name', 100)->change();
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->text('note')->nullable()->change();
        });
        Schema::table('documents', function (Blueprint $table): void {
            $table->text('note')->nullable()->change();
        });
        Schema::table('notifications', function (Blueprint $table): void {
            $table->text('message')->change();
            $table->timestamp('notification_date')->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('message', 500)->change();
            $table->date('notification_date')->change();
        });
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('note', 300)->nullable()->change();
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->string('note', 300)->nullable()->change();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name', 255)->change();
        });
    }
};
