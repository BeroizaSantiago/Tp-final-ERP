<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enriquece las fichas que usa el asistente y agrega un lugar para guardar
     * contenido aprobado (por ejemplo, una sinopsis revisada por un humano).
     */
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->text('biography')->nullable()->change();
            $table->string('nationality')->nullable()->after('biography');
            $table->unsignedSmallInteger('birth_year')->nullable()->after('nationality');
            $table->unsignedSmallInteger('death_year')->nullable()->after('birth_year');
            $table->string('website')->nullable()->after('death_year');
            $table->text('notes')->nullable()->after('website');
        });

        Schema::table('publishers', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->unsignedSmallInteger('founded_year')->nullable()->after('website');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        Schema::create('ai_enrichments', function (Blueprint $table) {
            $table->id();
            $table->morphs('enrichable');
            $table->string('kind', 50);
            $table->longText('content');
            $table->string('source', 30)->default('manual');
            $table->string('model', 100)->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(
                ['enrichable_type', 'enrichable_id', 'kind', 'status'],
                'ai_enrichments_lookup_index'
            );
        });

        Schema::table('request_ia', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('detalle_venta_id')
                ->constrained('products')
                ->nullOnDelete();

            $table->foreignId('author_id')
                ->nullable()
                ->after('product_id')
                ->constrained('authors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_ia', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
            $table->dropConstrainedForeignId('product_id');
        });

        Schema::dropIfExists('ai_enrichments');

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('publishers', function (Blueprint $table) {
            $table->dropColumn(['description', 'founded_year']);
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn(['nationality', 'birth_year', 'death_year', 'website', 'notes']);
            $table->string('biography')->nullable()->change();
        });
    }
};
