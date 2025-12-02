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
        Schema::create('domain_email_with_details', function (Blueprint $table) {
            $table->id();
            $table->string('owner');
            $table->string('domain');
            $table->string('category');
            $table->string('email');
            $table->string('phone');
            $table->text('address');
            $table->text('services');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_email_with_details');
    }
};
