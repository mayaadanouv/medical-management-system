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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('department_id')->constrained('departments');
            $table->string('syndicate_number')->unique();
            $table->string('certificate_image');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('experience_years');//سنوات الخبرة
            $table->text('bio')->nullable();
            $table->string('clinic_location')->nullable();// موقع العيادة ي حال الوجود
            $table->string('whatsapp_url');
            $table->string('profile_image')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
