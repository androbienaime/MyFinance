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
        Schema::create('identity_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->string('document_number');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->foreignId("country_id")->nullable();
            $table->boolean("is_primary")->default(false);
            $table->boolean("is_verified")->default(false);
            $table->string("verified_by")->nullable();
            $table->boolean("is_active")->default(true);
            $table->string("notes")->nullable();

            $table->uuidMorphs('identity_documentable', 'identity_doc_morph_idx');           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_documents');
    }
};
