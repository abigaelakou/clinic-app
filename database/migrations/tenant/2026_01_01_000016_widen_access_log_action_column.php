<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE medical_record_access_logs MODIFY action TEXT');
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE medical_record_access_logs MODIFY action VARCHAR(255)');
    }
};
