<?php

namespace App\Support\Auth;

use App\Models\AdminUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class AdminGuard implements Guard
{
    protected ?Authenticatable $user = null;
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function check(): bool
    {
        return !is_null($this->user());
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function getRequest(): Request
    {
        return app()->bound('request') ? app('request') : $this->request;
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $request = $this->getRequest();
        $token = AdminSessionManager::extractTokenFromRequest($request);

        if (!$token) {
            return null;
        }

        $admin = AdminSessionManager::authenticate($token);
        if ($admin) {
            $this->user = $admin;
        }

        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user() ? $this->user()->getAuthIdentifier() : null;
    }

    public function validate(array $credentials = []): bool
    {
        return false; // Validated via dedicated auth service
    }

    public function hasUser(): bool
    {
        return !is_null($this->user);
    }

    public function setUser(Authenticatable $user): self
    {
        $this->user = $user;
        return $this;
    }
}
