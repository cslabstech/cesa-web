<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leads', 'public_response_id')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->dropPublicResponseIdFromSqlite();

            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropUnique(['public_response_id']);
            $table->dropColumn('public_response_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'public_response_id')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->string('public_response_id')->nullable()->unique()->after('phone_transaction_range');
        });
    }

    private function dropPublicResponseIdFromSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            DB::statement(<<<'SQL'
            CREATE TABLE "__temp__leads" (
                "id" integer primary key autoincrement not null,
                "name" varchar not null,
                "phone" varchar not null,
                "address" text not null,
                "sales_person" varchar not null,
                "store_team_position" varchar check ("store_team_position" in ('Kepala Toko', 'Promotor', 'Kasir', 'Frontliner')) not null,
                "store_branch" varchar not null,
                "phone_transaction_range" varchar check ("phone_transaction_range" in ('Harga di bawah 2 juta', 'Harga 2 - 3 juta', 'Harga 3 - 4 juta', 'Harga 4 - 7 juta', 'Harga di atas 7 juta')),
                "creator_id" integer,
                "created_at" datetime,
                "updated_at" datetime,
                "deleted_at" datetime,
                foreign key("creator_id") references "users"("id") on delete set null on update no action
            )
        SQL);

            DB::statement(<<<'SQL'
            INSERT INTO "__temp__leads" (
                "id",
                "name",
                "phone",
                "address",
                "sales_person",
                "store_team_position",
                "store_branch",
                "phone_transaction_range",
                "creator_id",
                "created_at",
                "updated_at",
                "deleted_at"
            )
            SELECT
                "id",
                "name",
                "phone",
                "address",
                "sales_person",
                "store_team_position",
                "store_branch",
                "phone_transaction_range",
                "creator_id",
                "created_at",
                "updated_at",
                "deleted_at"
            FROM "leads"
        SQL);

            DB::statement('DROP TABLE "leads"');
            DB::statement('ALTER TABLE "__temp__leads" RENAME TO "leads"');
            DB::statement('CREATE UNIQUE INDEX "leads_phone_unique" on "leads" ("phone")');
            DB::statement('CREATE INDEX "leads_store_branch_created_at_index" on "leads" ("store_branch", "created_at")');
            DB::statement('CREATE INDEX "leads_store_team_position_created_at_index" on "leads" ("store_team_position", "created_at")');
            DB::statement('CREATE INDEX "leads_sales_person_created_at_index" on "leads" ("sales_person", "created_at")');
            DB::statement('CREATE INDEX "leads_created_at_index" on "leads" ("created_at")');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
