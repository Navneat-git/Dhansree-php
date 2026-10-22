<?php

namespace Tests\Unit\Rbac;

use App\Enums\AdminSessionRevokeReason;
use App\Enums\AdminUserStatus;
use App\Enums\AuditActorType;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\AdminSession;
use App\Models\AdminUser;
use App\Support\Audit\AuditLogger;
use App\Support\Auth\AdminSessionManager;
use App\Support\Rbac\AdminAuthorizer;
use App\Support\Rbac\Permissions;
use Carbon\Carbon;
use Tests\TestCase;

class AdminRbacTest extends TestCase
{
    public function test_permissions_registry_contains_39_codes(): void
    {
        $all = Permissions::all();
        $this->assertCount(39, $all);

        foreach ($all as $code) {
            $this->assertTrue(Permissions::isValid($code));
            $this->assertNotEmpty(Permissions::DESCRIPTIONS[$code]);
        }
    }

    public function test_super_admin_grants_all_permissions(): void
    {
        $superRole = new AdminRole([
            'code' => 'SUPER_ADMIN',
            'name' => 'Super Admin',
            'grants_all_permissions' => true,
        ]);
        $superRole->id = 1;

        $admin = new AdminUser([
            'email' => 'super@dhansree.com',
            'name' => 'Super User',
            'status' => AdminUserStatus::ACTIVE,
        ]);
        $admin->id = 1;
        $admin->setRelation('roles', collect([$superRole]));

        $effective = AdminAuthorizer::resolveEffectivePermissions($admin);
        $this->assertCount(39, $effective);

        $this->assertTrue(AdminAuthorizer::hasAccess($admin, Permissions::PRODUCT_DELETE));
        $this->assertTrue(AdminAuthorizer::hasAccess($admin, [Permissions::PAYMENT_REFUND, Permissions::ADMIN_USER_DELETE]));
    }

    public function test_role_with_specific_permissions_enforces_and_semantics(): void
    {
        $orderPerm1 = new AdminPermission(['code' => Permissions::ORDER_VIEW]);
        $orderPerm2 = new AdminPermission(['code' => Permissions::ORDER_UPDATE]);

        $orderRole = new AdminRole([
            'code' => 'ORDER_MANAGER',
            'name' => 'Order Manager',
            'grants_all_permissions' => false,
        ]);
        $orderRole->id = 2;
        $orderRole->setRelation('permissions', collect([$orderPerm1, $orderPerm2]));

        $admin = new AdminUser([
            'email' => 'orders@dhansree.com',
            'status' => AdminUserStatus::ACTIVE,
        ]);
        $admin->id = 2;
        $admin->setRelation('roles', collect([$orderRole]));

        // Can view and update orders
        $this->assertTrue(AdminAuthorizer::hasAccess($admin, Permissions::ORDER_VIEW));
        $this->assertTrue(AdminAuthorizer::hasAccess($admin, [Permissions::ORDER_VIEW, Permissions::ORDER_UPDATE]));

        // Cannot refund or delete products (AND semantics fails if any missing)
        $this->assertFalse(AdminAuthorizer::hasAccess($admin, Permissions::PAYMENT_REFUND));
        $this->assertFalse(AdminAuthorizer::hasAccess($admin, [Permissions::ORDER_VIEW, Permissions::PAYMENT_REFUND]));
    }

    public function test_admin_session_token_format_validation(): void
    {
        $token = AdminSessionManager::generateToken();
        $this->assertSame(43, strlen($token));
        $this->assertTrue(AdminSessionManager::isValidTokenFormat($token));

        $this->assertFalse(AdminSessionManager::isValidTokenFormat('short'));
        $this->assertFalse(AdminSessionManager::isValidTokenFormat('invalid*chars!in@token#with$symbols%format^here'));
    }

    public function test_audit_logger_redaction_and_truncation(): void
    {
        $input = [
            'admin_email' => 'staff@dhansree.com',
            'password' => 'secret-password-123',
            'secret_token' => 'top-secret-val',
            'card_number' => '4111222233334444',
            'nested' => [
                'auth_token' => 'bearer-12345',
                'description' => str_repeat('A', 600),
            ],
        ];

        $sanitized = AuditLogger::sanitizeMetadata($input);

        $this->assertEquals('staff@dhansree.com', $sanitized['admin_email']);
        $this->assertEquals('[redacted]', $sanitized['password']);
        $this->assertEquals('[redacted]', $sanitized['secret_token']);
        $this->assertEquals('[redacted]', $sanitized['card_number']);
        $this->assertEquals('[redacted]', $sanitized['nested']['auth_token']);
        $this->assertStringContainsString('...[truncated]', $sanitized['nested']['description']);
        $this->assertLessThan(550, strlen($sanitized['nested']['description']));
    }
}
