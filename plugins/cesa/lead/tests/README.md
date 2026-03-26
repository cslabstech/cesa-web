# Lead Plugin - Test Suite

Plugin `lead` saat ini memiliki baseline regression coverage yang aktif untuk bootstrap plugin, model, policy, validasi, public submission, dan integrasi. Suite Filament resource/table masih dikarantina sampai harness panel khusus plugin tersedia.

## 📋 Test Files

### Unit Tests (`tests/Unit/`)

1. **LeadModelTest.php** - 36 tests
   - Phone normalization (berbagai format: 08xx, +62, 620, 0062, dst)
   - Name transformation (uppercase, multibyte, special characters)
   - Soft deletes (delete, restore, query trashed)
   - Relationships (createdBy)
   - Mass assignment & fillable attributes
   - Factory validation
   - Database casts

2. **LeadPolicyTest.php** - 27 tests
   - Authorization untuk semua actions (viewAny, view, create, update, delete, restore, dll)
   - Permission checks
   - Resource permission scopes (Global, Group, Individual)
   - Multiple permissions combination

### Feature Tests (`tests/Feature/`)

1. **PhoneNormalizationTest.php** - 29 tests dengan data providers
   - Test normalisasi dari berbagai format (08, 62, +62, 620, 0062, 00, 8)
   - Edge cases (emoji, unicode, very long/short numbers)
   - Indonesian mobile prefixes (0811, 0812, 0821, dst)
   - Landline formats
   - Consistency across updates

2. **LeadValidationTest.php** - 33 tests
   - Validation rules untuk semua fields
   - Required fields validation
   - Max length validation
   - Phone uniqueness constraint
   - Phone format validation (harus 62xxxxxxxxxx dengan minimal 10 digit)
   - Enum validation (jabatan_tim_toko, range_transaksi_handphone)
   - Nullable fields
   - Multiple validation errors

3. **LeadPluginSmokeTest.php** - smoke tests
   - Plugin identity
   - Migration registration
   - Shield permission registration

4. **LeadResourceTest.php** - quarantined
   - Memerlukan harness Filament panel plugin-aware
   - Tidak dihitung sebagai sinyal CI sampai harness khusus tersedia

5. **LeadTableTest.php** - quarantined
   - Memerlukan harness Filament panel plugin-aware
   - Tidak dihitung sebagai sinyal CI sampai harness khusus tersedia

6. **LeadIntegrationTest.php** - 21 tests
   - End-to-end scenarios
   - Database constraints & indexes
   - Cascade delete behavior
   - Complex queries dengan multiple conditions
   - Data integrity (soft delete, restore)
   - Factory state consistency
   - Eager loading relationships
   - All cabang_toko options storage
   - Enum constraints enforcement

## 📊 Test Coverage Summary

**Active CI Signal**
- Unit tests: model + policy
- Feature tests: phone normalization, validation, public submission, integration, plugin smoke
- Quarantined: `LeadResourceTest`, `LeadTableTest`

**Coverage Areas:**
- Model Mutators & Accessors ✓
- Database Operations ✓
- Soft Deletes ✓
- Relationships ✓
- Authorization & Policies ✓
- Validation Rules ✓
- Filament CRUD Operations: quarantined
- Table Functionality: quarantined
- Edge Cases & Error Handling ✓
- Data Integrity ✓

## 🚀 Cara Menjalankan Tests

### Setup Database Test

Sebelum menjalankan tests, pastikan migration sudah dijalankan di test database:

```bash
# Run migrations untuk testing environment
php artisan migrate:fresh --env=testing
php artisan migrate --path=plugins/cesa/lead/database/migrations --env=testing
```

### Menjalankan Semua Tests

```bash
# Recommended active plugin suites
php artisan test plugins/cesa/lead/tests/Unit
php artisan test plugins/cesa/lead/tests/Feature/PhoneNormalizationTest.php
php artisan test plugins/cesa/lead/tests/Feature/LeadValidationTest.php
php artisan test plugins/cesa/lead/tests/Feature/PublicLeadSubmissionTest.php
php artisan test plugins/cesa/lead/tests/Feature/LeadIntegrationTest.php
php artisan test plugins/cesa/lead/tests/Feature/LeadPluginSmokeTest.php

# Dengan coverage (requires Xdebug)
php artisan test plugins/cesa/lead/tests
```

### Menjalankan Test Spesifik

```bash
# Unit tests saja
php artisan test plugins/cesa/lead/tests/Unit

# Feature tests saja
php artisan test plugins/cesa/lead/tests/Feature

# File test spesifik
php artisan test plugins/cesa/lead/tests/Unit/LeadModelTest.php

# Method test spesifik
php artisan test --filter=test_phone_normalizes_from_08_format
```

## ⚙️ Konfigurasi

Tests menggunakan:
- **Plugin-scoped SQLite harness**: Database test dibuat ulang per test case
- **Spatie Permission**: Untuk testing authorization
- **Livewire testing utilities**: Untuk public form dan interaksi komponen non-panel
- **Factory patterns**: Untuk generate test data

## 🔍 Skenario Detail yang Di-test

### Phone Normalization
- ✓ Format 08xxxxxxxxxx → 628xxxxxxxxxx
- ✓ Format 62xxxxxxxxxx (sudah benar)
- ✓ Format 620xxxxxxxxxx → 628xxxxxxxxxx
- ✓ Format 0062xxxxxxxxxx → 628xxxxxxxxxx
- ✓ Format +62xxxxxxxxxx → 628xxxxxxxxxx
- ✓ Format 8xxxxxxxxxx → 628xxxxxxxxxx
- ✓ Dengan separators (-, spaces, dots, parentheses)
- ✓ Dengan emoji & unicode characters
- ✓ Very short & very long numbers
- ✓ All Indonesian mobile prefixes (0811-0858)
- ✓ Landline formats (021xxxxxxxx)

### Validation Edge Cases
- ✓ Exactly max length values
- ✓ Over max length values
- ✓ Empty strings
- ✓ Null values where applicable
- ✓ Invalid enum values
- ✓ Phone uniqueness (create vs update)
- ✓ Multiple simultaneous validation errors

### Database & Data Integrity
- ✓ Unique constraints
- ✓ Foreign key relationships
- ✓ Cascade delete behavior
- ✓ Enum constraints
- ✓ Soft delete preservation
- ✓ Restore data integrity
- ✓ Timestamps accuracy
- ✓ Index existence

### Filament Components
- Quarantined pending panel-aware harness

## 🐛 Known Issues & Notes

1. **Data Providers**: PhpUnit 11 deprecated doc-comment metadata. Update ke attributes jika upgrade ke PHPUnit 12.

2. **Filament Panel**: `LeadResourceTest` dan `LeadTableTest` masih dikarantina karena harness panel plugin-aware belum tersedia.

3. **Permissions**: Setiap test yang menggunakan authorization akan create permissions yang dibutuhkan di `setUp()`.

4. **Test Database**: Menggunakan SQLite file sementara per test case. Beberapa constraints tetap berbeda dengan MySQL production.

## 📝 Maintenance Notes

Saat menambah features baru ke Lead plugin:

1. Update model tests jika ada mutators/accessors baru
2. Update policy tests jika ada permissions baru
3. Update resource tests jika ada perubahan form/table
4. Update validation tests jika ada rules baru
5. Update integration tests untuk end-to-end scenarios

## ✅ Checklist sebelum Deploy

- [ ] Active plugin tests passing
- [ ] Code coverage > 80%
- [ ] No deprecated warnings
- [ ] Migration tested di clean database
- [ ] Factory seeder works correctly
- [ ] Permissions seeded properly

---

**Last Updated**: 2026-03-26
**Test Framework**: Pest / PHPUnit
**Laravel Version**: 11.x
**Filament Version**: 4.x
