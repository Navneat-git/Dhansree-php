<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\Customer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class CustomerGuard implements Guard
{
    protected ?Authenticatable $user = null;
    protected ?string $currentToken = null;
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
        $request = $this->getRequest();
        $token = CustomerSessionManager::extractTokenFromRequest($request);

        if (!$token) {
            $this->user = null;
            $this->currentToken = null;
            return null;
        }

        if ($this->user !== null && $this->currentToken === $token) {
            return $this->user;
        }

        $customer = CustomerSessionManager::authenticate($token);
        if ($customer) {
            $this->user = $customer;
            $this->currentToken = $token;
        } else {
            $this->user = null;
            $this->currentToken = null;
        }

        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user() ? (int) $this->user()->getAuthIdentifier() : null;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return !is_null($this->user);
    }

    public function setUser(Authenticatable $user): self
    {
        $this->user = $user;
        $this->currentToken = null;
        return $this;
    }

    public function logout(): void
    {
        $request = $this->getRequest();
        $token = CustomerSessionManager::extractTokenFromRequest($request);
        if ($token) {
            CustomerSessionManager::revokeSession($token);
        }
        $this->user = null;
        $this->currentToken = null;
    }
}
