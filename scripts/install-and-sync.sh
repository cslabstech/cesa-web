#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SCRIPT_NAME="$(basename "$0")"
FAIL_STEP="initialization"

trap 'echo >&2; echo "Failed during: ${FAIL_STEP}" >&2' ERR

dotenv_value() {
    local key="$1"

    php -r '
$path = $argv[1];
$key = $argv[2];

if (! is_file($path)) {
    exit(1);
}

$lines = file($path, FILE_IGNORE_NEW_LINES);

if ($lines === false) {
    exit(1);
}

foreach ($lines as $line) {
    $line = trim($line);

    if ($line === "" || str_starts_with($line, "#") || ! str_contains($line, "=")) {
        continue;
    }

    [$candidateKey, $candidateValue] = explode("=", $line, 2);

    if ($candidateKey !== $key) {
        continue;
    }

    $candidateValue = trim($candidateValue);

    if (($candidateValue[0] ?? "") === "\"" && str_ends_with($candidateValue, "\"")) {
        $candidateValue = substr($candidateValue, 1, -1);
    }

    if (($candidateValue[0] ?? "") === "'"'"'" && str_ends_with($candidateValue, "'"'"'")) {
        $candidateValue = substr($candidateValue, 1, -1);
    }

    if (strcasecmp($candidateValue, "null") === 0) {
        $candidateValue = "";
    }

    echo $candidateValue;
    exit(0);
}

exit(1);
' "$ROOT_DIR/.env" "$key" 2>/dev/null
}

resolve_setting() {
    local fallback="$1"
    shift

    local key
    local value

    for key in "$@"; do
        value="${!key:-}"

        if [[ -n "$value" ]]; then
            printf '%s' "$value"
            return 0
        fi

        if value="$(dotenv_value "$key")" && [[ -n "$value" ]]; then
            printf '%s' "$value"
            return 0
        fi
    done

    printf '%s' "$fallback"
}

usage() {
    cat <<EOF
Usage:
  bash scripts/${SCRIPT_NAME} [options]

Flow:
  1. erp:install
  2. migrate legacy-sync
  3. install document, exit-clearance, form-transfer, rekrutmen
  4. generate Shield permissions
  5. sync legacy modules: document, form-transfer, exit-clearance
  6. grant admin + synced users full access

Options:
  --help                         Show this help
  --force                        Pass --force to erp:install
  --truncate                     Pass --truncate to legacy:sync
  --skip-erp-install             Skip erp:install
  --skip-plugin-install          Skip plugin install commands
  --skip-permissions             Skip shield permission generation
  --skip-sync                    Skip legacy sync
  --skip-grant-access            Skip granting Admin/global/form-transfer access
  --skip-email-verify            Do not set email_verified_at during access sync
  --trust-legacy-user-ids        Pass --trust-legacy-user-ids to legacy:sync
  --trust-legacy-company-ids     Pass --trust-legacy-company-ids to legacy:sync
  --admin-name=VALUE             Override ERP admin name
  --admin-email=VALUE            Override ERP admin email
  --admin-password=VALUE         Override ERP admin password
  --legacy-db-host=VALUE         Override legacy DB host
  --legacy-db-port=VALUE         Override legacy DB port
  --legacy-db-name=VALUE         Override legacy DB database
  --legacy-db-username=VALUE     Override legacy DB username
  --legacy-db-password=VALUE     Override legacy DB password
  --legacy-sync-chunk=VALUE      Override chunk size for legacy sync

Environment overrides:
  ERP_ADMIN_NAME
  ERP_ADMIN_EMAIL
  ERP_ADMIN_PASSWORD
  LEGACY_DB_HOST
  LEGACY_DB_PORT
  LEGACY_DB_NAME
  LEGACY_DB_USERNAME
  LEGACY_DB_PASSWORD
  LEGACY_SYNC_CHUNK
  ERP_FORCE
  LEGACY_TRUNCATE
  TRUST_LEGACY_USER_IDS
  TRUST_LEGACY_COMPANY_IDS
  SKIP_ERP_INSTALL
  SKIP_PLUGIN_INSTALL
  SKIP_PERMISSIONS
  SKIP_SYNC
  GRANT_SYNCED_USERS_FULL_ACCESS
  MARK_USERS_EMAIL_VERIFIED
EOF
}

run() {
    FAIL_STEP="$*"
    echo
    echo "+ $*"
    "$@"
}

build_boolean_flag() {
    local flag_name="$1"
    local flag_value="$2"

    if [[ "$flag_value" == "1" ]]; then
        printf '%s' "--${flag_name}"
    fi
}

ERP_ADMIN_NAME="$(resolve_setting "Admin" ERP_ADMIN_NAME)"
ERP_ADMIN_EMAIL="$(resolve_setting "admin@cesa.completeselular.com" ERP_ADMIN_EMAIL)"
ERP_ADMIN_PASSWORD="$(resolve_setting "admin123" ERP_ADMIN_PASSWORD)"

LEGACY_DB_HOST="$(resolve_setting "127.0.0.1" LEGACY_DB_HOST LEGACY_SYNC_DB_HOST DB_HOST)"
LEGACY_DB_PORT="$(resolve_setting "3306" LEGACY_DB_PORT LEGACY_SYNC_DB_PORT DB_PORT)"
LEGACY_DB_NAME="$(resolve_setting "app_cesa" LEGACY_DB_NAME LEGACY_SYNC_DB_DATABASE)"
LEGACY_DB_USERNAME="$(resolve_setting "root" LEGACY_DB_USERNAME LEGACY_SYNC_DB_USERNAME DB_USERNAME)"
LEGACY_DB_PASSWORD="$(resolve_setting "" LEGACY_DB_PASSWORD LEGACY_SYNC_DB_PASSWORD DB_PASSWORD)"
LEGACY_SYNC_CHUNK="$(resolve_setting "250" LEGACY_SYNC_CHUNK)"

ERP_FORCE="${ERP_FORCE:-0}"
LEGACY_TRUNCATE="${LEGACY_TRUNCATE:-0}"
TRUST_LEGACY_USER_IDS="${TRUST_LEGACY_USER_IDS:-0}"
TRUST_LEGACY_COMPANY_IDS="${TRUST_LEGACY_COMPANY_IDS:-0}"
SKIP_ERP_INSTALL="${SKIP_ERP_INSTALL:-0}"
SKIP_PLUGIN_INSTALL="${SKIP_PLUGIN_INSTALL:-0}"
SKIP_PERMISSIONS="${SKIP_PERMISSIONS:-0}"
SKIP_SYNC="${SKIP_SYNC:-0}"
GRANT_SYNCED_USERS_FULL_ACCESS="${GRANT_SYNCED_USERS_FULL_ACCESS:-1}"
MARK_USERS_EMAIL_VERIFIED="${MARK_USERS_EMAIL_VERIFIED:-1}"

while (($#)); do
    case "$1" in
        --help|-h)
            usage
            exit 0
            ;;
        --force)
            ERP_FORCE=1
            ;;
        --truncate)
            LEGACY_TRUNCATE=1
            ;;
        --skip-erp-install)
            SKIP_ERP_INSTALL=1
            ;;
        --skip-plugin-install)
            SKIP_PLUGIN_INSTALL=1
            ;;
        --skip-permissions)
            SKIP_PERMISSIONS=1
            ;;
        --skip-sync)
            SKIP_SYNC=1
            ;;
        --skip-grant-access)
            GRANT_SYNCED_USERS_FULL_ACCESS=0
            ;;
        --skip-email-verify)
            MARK_USERS_EMAIL_VERIFIED=0
            ;;
        --trust-legacy-user-ids)
            TRUST_LEGACY_USER_IDS=1
            ;;
        --trust-legacy-company-ids)
            TRUST_LEGACY_COMPANY_IDS=1
            ;;
        --admin-name=*)
            ERP_ADMIN_NAME="${1#*=}"
            ;;
        --admin-email=*)
            ERP_ADMIN_EMAIL="${1#*=}"
            ;;
        --admin-password=*)
            ERP_ADMIN_PASSWORD="${1#*=}"
            ;;
        --legacy-db-host=*)
            LEGACY_DB_HOST="${1#*=}"
            ;;
        --legacy-db-port=*)
            LEGACY_DB_PORT="${1#*=}"
            ;;
        --legacy-db-name=*)
            LEGACY_DB_NAME="${1#*=}"
            ;;
        --legacy-db-username=*)
            LEGACY_DB_USERNAME="${1#*=}"
            ;;
        --legacy-db-password=*)
            LEGACY_DB_PASSWORD="${1#*=}"
            ;;
        --legacy-sync-chunk=*)
            LEGACY_SYNC_CHUNK="${1#*=}"
            ;;
        *)
            echo "Unknown option: $1" >&2
            echo >&2
            usage >&2
            exit 1
            ;;
    esac

    shift
done

if [[ ! -f artisan ]]; then
    echo "artisan file not found. Run this script from the project root or keep it in scripts/." >&2
    exit 1
fi

echo "Starting install-and-sync flow..."
echo "Project root: $ROOT_DIR"
echo "Legacy database: ${LEGACY_DB_USERNAME}@${LEGACY_DB_HOST}:${LEGACY_DB_PORT}/${LEGACY_DB_NAME}"

if [[ "$SKIP_ERP_INSTALL" != "1" ]]; then
    erp_install_args=(
        php artisan erp:install
        "--admin-name=${ERP_ADMIN_NAME}"
        "--admin-email=${ERP_ADMIN_EMAIL}"
        "--admin-password=${ERP_ADMIN_PASSWORD}"
        --no-interaction
    )

    erp_force_flag="$(build_boolean_flag force "$ERP_FORCE")"

    if [[ -n "$erp_force_flag" ]]; then
        erp_install_args+=("$erp_force_flag")
    fi

    run "${erp_install_args[@]}"
else
    echo
    echo "- Skipping erp:install"
fi

run php artisan migrate --path=plugins/cesa/legacy-sync/database/migrations --realpath --force --no-interaction

if [[ "$SKIP_PLUGIN_INSTALL" != "1" ]]; then
    run php artisan document:install --no-interaction
    run php artisan exit-clearance:install --no-interaction
    run php artisan form-transfer:install --no-interaction
    run php artisan rekrutmen:install --no-interaction
else
    echo
    echo "- Skipping plugin install commands"
fi

if [[ "$SKIP_PERMISSIONS" != "1" ]]; then
    run php artisan shield:generate --all --option=permissions --panel=admin --no-interaction
    run php artisan permission:cache-reset
else
    echo
    echo "- Skipping Shield permission generation"
fi

run env \
    ERP_ADMIN_EMAIL_FOR_SCRIPT="$ERP_ADMIN_EMAIL" \
    MARK_USERS_EMAIL_VERIFIED_FOR_SCRIPT="$MARK_USERS_EMAIL_VERIFIED" \
    php artisan tinker --execute "$(cat <<'PHP'
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\Permission;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;

$role = Role::query()->firstOrCreate([
    'name' => (string) config('filament-shield.panel_user.name', 'Admin'),
    'guard_name' => 'web',
]);

$role->syncPermissions(Permission::query()->pluck('name')->all());

$admin = User::query()->where('email', env('ERP_ADMIN_EMAIL_FOR_SCRIPT', 'admin@cesa.completeselular.com'))->first();

if ($admin) {
    $admin->resource_permission = PermissionType::GLOBAL;
    $admin->has_all_form_transfer_access = true;

    if ((bool) env('MARK_USERS_EMAIL_VERIFIED_FOR_SCRIPT', true) && blank($admin->email_verified_at)) {
        $admin->email_verified_at = now();
    }

    $admin->save();

    if (! $admin->hasRole($role)) {
        $admin->assignRole($role);
    }
}
PHP
)"

if [[ "$SKIP_SYNC" != "1" ]]; then
    legacy_sync_args=(
        php artisan legacy:sync
        --module=document
        --module=form-transfer
        --module=exit-clearance
        "--database=${LEGACY_DB_NAME}"
        "--host=${LEGACY_DB_HOST}"
        "--port=${LEGACY_DB_PORT}"
        "--username=${LEGACY_DB_USERNAME}"
        "--password=${LEGACY_DB_PASSWORD}"
        "--chunk=${LEGACY_SYNC_CHUNK}"
        --no-interaction
    )

    legacy_truncate_flag="$(build_boolean_flag truncate "$LEGACY_TRUNCATE")"
    trust_legacy_user_ids_flag="$(build_boolean_flag trust-legacy-user-ids "$TRUST_LEGACY_USER_IDS")"
    trust_legacy_company_ids_flag="$(build_boolean_flag trust-legacy-company-ids "$TRUST_LEGACY_COMPANY_IDS")"

    if [[ -n "$legacy_truncate_flag" ]]; then
        legacy_sync_args+=("$legacy_truncate_flag")
    fi

    if [[ -n "$trust_legacy_user_ids_flag" ]]; then
        legacy_sync_args+=("$trust_legacy_user_ids_flag")
    fi

    if [[ -n "$trust_legacy_company_ids_flag" ]]; then
        legacy_sync_args+=("$trust_legacy_company_ids_flag")
    fi

    run "${legacy_sync_args[@]}"
else
    echo
    echo "- Skipping legacy sync"
fi

if [[ "$GRANT_SYNCED_USERS_FULL_ACCESS" == "1" ]]; then
    run env \
        ERP_ADMIN_EMAIL_FOR_SCRIPT="$ERP_ADMIN_EMAIL" \
        MARK_USERS_EMAIL_VERIFIED_FOR_SCRIPT="$MARK_USERS_EMAIL_VERIFIED" \
        php artisan tinker --execute "$(cat <<'PHP'
use Illuminate\Support\Facades\DB;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;

$role = Role::query()->where('name', (string) config('filament-shield.panel_user.name', 'Admin'))->first();

$userIds = collect();

$adminId = User::query()
    ->where('email', env('ERP_ADMIN_EMAIL_FOR_SCRIPT', 'admin@cesa.completeselular.com'))
    ->value('id');

if ($adminId) {
    $userIds->push((int) $adminId);
}

if (DB::getSchemaBuilder()->hasTable('legacy_sync_mappings')) {
    $mappedUserIds = DB::table('legacy_sync_mappings')
        ->where('legacy_table', 'users')
        ->where('target_table', 'users')
        ->pluck('target_id')
        ->map(static fn ($id) => (int) $id)
        ->all();

    $userIds = $userIds->merge($mappedUserIds);
}

foreach ($userIds->unique()->values() as $userId) {
    $user = User::query()->find($userId);

    if (! $user) {
        continue;
    }

    $user->resource_permission = PermissionType::GLOBAL;
    $user->has_all_form_transfer_access = true;

    if ((bool) env('MARK_USERS_EMAIL_VERIFIED_FOR_SCRIPT', true) && blank($user->email_verified_at)) {
        $user->email_verified_at = now();
    }

    $user->save();

    if ($role && ! $user->hasRole($role)) {
        $user->assignRole($role);
    }
}
PHP
)"

    run php artisan permission:cache-reset
else
    echo
    echo "- Skipping full access grant for synced users"
fi

run php artisan tinker --execute "$(cat <<'PHP'
use Illuminate\Support\Facades\DB;

$companiesCount = DB::getSchemaBuilder()->hasTable('companies') ? DB::table('companies')->count() : 0;
$documentsCount = DB::getSchemaBuilder()->hasTable('documents') ? DB::table('documents')->count() : 0;
$formTransfersCount = DB::getSchemaBuilder()->hasTable('form_transfers') ? DB::table('form_transfers')->count() : 0;
$transferRequestsCount = DB::getSchemaBuilder()->hasTable('form_transfer_requests') ? DB::table('form_transfer_requests')->count() : 0;
$exitRequestsCount = DB::getSchemaBuilder()->hasTable('exit_clearance_requests') ? DB::table('exit_clearance_requests')->count() : 0;
$userMappingsCount = DB::getSchemaBuilder()->hasTable('legacy_sync_mappings')
    ? DB::table('legacy_sync_mappings')->where('legacy_table', 'users')->where('target_table', 'users')->count()
    : 0;

echo 'Summary'.PHP_EOL;
echo '-------'.PHP_EOL;
echo 'companies='.$companiesCount.PHP_EOL;
echo 'documents='.$documentsCount.PHP_EOL;
echo 'form_transfers='.$formTransfersCount.PHP_EOL;
echo 'form_transfer_requests='.$transferRequestsCount.PHP_EOL;
echo 'exit_clearance_requests='.$exitRequestsCount.PHP_EOL;
echo 'synced_user_mappings='.$userMappingsCount.PHP_EOL;
PHP
)"

echo
echo "Done."
echo "Flow completed: erp:install -> plugin install -> permissions -> legacy sync -> access sync"
echo "Installed plugins: document, exit-clearance, form-transfer, rekrutmen"
echo "Synced modules: document, form-transfer, exit-clearance"
