<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_mails', function (Blueprint $table): void {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->string('customer_name');
            $table->string('category')->default('unknown');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_mails');
    }
};
