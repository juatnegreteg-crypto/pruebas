<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillUsers();
        $this->backfillTechnicians();
        $this->backfillCustomers();

        Schema::table('technicians', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->unique('user_id');
        });
    }

    private function backfillUsers(): void
    {
        $users = DB::table('users')
            ->select('id', 'name', 'full_name', 'email', 'is_active', 'email_verified_at', 'party_id')
            ->whereNull('party_id')
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $fullName = trim((string) ($user->full_name ?? ''));
            $nameFallback = trim((string) ($user->name ?? ''));
            $displayName = $fullName !== '' ? $fullName : ($nameFallback !== '' ? $nameFallback : "user-{$user->id}");

            $partyId = DB::table('parties')->insertGetId([
                'type' => 'person',
                'display_name' => $displayName,
                'is_active' => (bool) ($user->is_active ?? true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('party_people')->insert([
                'party_id' => $partyId,
                'full_name' => $displayName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($user->email !== null && trim((string) $user->email) !== '') {
                DB::table('party_emails')->insert([
                    'party_id' => $partyId,
                    'email' => Str::lower(trim((string) $user->email)),
                    'type' => 'primary',
                    'is_primary' => true,
                    'is_verified' => $user->email_verified_at !== null,
                    'verified_at' => $user->email_verified_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('users')->where('id', $user->id)->update([
                'party_id' => $partyId,
            ]);
        }
    }

    private function backfillTechnicians(): void
    {
        $technicians = DB::table('technicians')
            ->select('id', 'user_id', 'party_id', 'is_active')
            ->whereNull('party_id')
            ->orderBy('id')
            ->get();

        foreach ($technicians as $technician) {
            $partyId = null;

            if ($technician->user_id !== null) {
                $partyId = DB::table('users')
                    ->where('id', $technician->user_id)
                    ->value('party_id');
            }

            if ($partyId === null) {
                $displayName = "technician-{$technician->id}";

                $partyId = DB::table('parties')->insertGetId([
                    'type' => 'person',
                    'display_name' => $displayName,
                    'is_active' => (bool) ($technician->is_active ?? true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('party_people')->insert([
                    'party_id' => $partyId,
                    'full_name' => $displayName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('technicians')->where('id', $technician->id)->update([
                'party_id' => $partyId,
            ]);
        }
    }

    private function backfillCustomers(): void
    {
        $customers = DB::table('customers')
            ->select('id', 'full_name', 'email', 'document_type', 'document_number', 'phone_number', 'party_id')
            ->whereNull('party_id')
            ->orderBy('id')
            ->get();

        foreach ($customers as $customer) {
            $normalizedDocumentType = Str::upper((string) ($customer->document_type ?? ''));
            $isOrganization = $normalizedDocumentType === 'NIT';
            $displayName = trim((string) ($customer->full_name ?? '')) !== ''
                ? trim((string) $customer->full_name)
                : "customer-{$customer->id}";

            $partyId = DB::table('parties')->insertGetId([
                'type' => $isOrganization ? 'organization' : 'person',
                'display_name' => $displayName,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($isOrganization) {
                DB::table('party_organizations')->insert([
                    'party_id' => $partyId,
                    'legal_name' => $displayName,
                    'trade_name' => null,
                    'tax_id' => trim((string) ($customer->document_number ?? '')) ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('party_people')->insert([
                    'party_id' => $partyId,
                    'full_name' => $displayName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($customer->email !== null && trim((string) $customer->email) !== '') {
                $normalizedEmail = Str::lower(trim((string) $customer->email));
                DB::table('party_emails')->insert([
                    'party_id' => $partyId,
                    'email' => $normalizedEmail,
                    'type' => 'primary',
                    'is_primary' => true,
                    'is_verified' => false,
                    'verified_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($customer->phone_number !== null && trim((string) $customer->phone_number) !== '') {
                DB::table('party_phones')->insert([
                    'party_id' => $partyId,
                    'phone_number' => trim((string) $customer->phone_number),
                    'type' => 'primary',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('customers')->where('id', $customer->id)->update([
                'party_id' => $partyId,
            ]);
        }
    }
};
