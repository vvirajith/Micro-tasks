<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id');

            // Declared before the foreign key so InnoDB reuses this index for
            // the constraint instead of silently adding a second one. The
            // uniqueness is what enforces the one-profile-per-user relation.
            $table->unique('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('company_name', 150);
            // E.164 allows at most 15 digits plus a leading "+".
            $table->string('phone', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
