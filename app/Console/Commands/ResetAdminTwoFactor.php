<?php

namespace App\Console\Commands;

use App\Enums\AdminSessionRevokeReason;
use App\Enums\AuditActorType;
use App\Models\AdminLoginChallenge;
use App\Models\AdminRecoveryCode;
use App\Models\AdminSession;
use App\Models\AdminTotpCredential;
use App\Models\AdminUser;
use App\Support\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:2fa:reset {email : Email address of the administrator}';
    protected $description = 'Emergency recovery: reset two-factor authentication and revoke sessions for an administrator';

    public function handle(AuditLogger $audit): int
    {
        $email = trim(strtolower($this->argument('email')));
        $account = AdminUser::where('email', $email)->first();

        if (!$account) {
            $this->error("No admin account with that email address.");
            return 1;
        }

        $credential = AdminTotpCredential::where('admin_user_id', $account->id)->first();
        if (!$credential) {
            $this->info("{$account->name} has no authenticator set up. Nothing to reset.");
            return 0;
        }

        $now = Carbon::now('UTC');

        $sessionsEnded = DB::transaction(function () use ($account, $credential, $now, $audit) {
            AdminRecoveryCode::where('credential_id', $credential->id)->delete();
            $credential->delete();

            AdminLoginChallenge::where('admin_user_id', $account->id)->delete();

            $ended = AdminSession::where('admin_user_id', $account->id)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => $now,
                    'revoke_reason' => AdminSessionRevokeReason::REVOKED_BY_ADMIN,
                ]);

            $audit->log(
                action: 'admin.2fa.reset',
                actorType: AuditActorType::SYSTEM,
                entityType: 'admin_user',
                entityId: $account->id,
                metadata: ['sessionsEnded' => $ended]
            );

            return $ended;
        });

        $this->info("Two-step sign-in reset for {$account->name}. Sessions ended: {$sessionsEnded}.");
        $this->comment("They can now sign in with their password and will be prompted to set up 2FA again if required.");

        return 0;
    }
}
