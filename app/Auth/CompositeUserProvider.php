<?php

namespace App\Auth;

use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Schema;

class CompositeUserProvider implements UserProvider
{
    public function __construct(protected Hasher $hasher)
    {
    }

    public function retrieveById($identifier)
    {
        if (is_string($identifier) && str_starts_with($identifier, 'staff:')) {
            if (!Schema::hasTable('staff_accounts')) {
                return null;
            }
            $id = (int) substr($identifier, 6);
            $staff = $id > 0 ? StaffAccount::find($id) : null;
            if ($staff && $staff->is_approved) {
                $staff->markOnline();
            }
            return $staff;
        }

        return User::find($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        $model = $this->retrieveById($identifier);
        if (!$model) {
            return null;
        }

        $rememberToken = $model->getRememberToken();
        return $rememberToken && hash_equals($rememberToken, $token) ? $model : null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $timestamps = $user->timestamps;
        $user->timestamps = false;
        $user->save();
        $user->timestamps = $timestamps;
    }

    public function retrieveByCredentials(array $credentials)
    {
        $filtered = array_filter(
            $credentials,
            fn ($key) => !str_contains((string) $key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($filtered)) {
            return null;
        }

        $userQuery = User::query();
        foreach ($filtered as $key => $value) {
            $userQuery->where($key, $value);
        }
        $user = $userQuery->first();
        if ($user) {
            return $user;
        }

        if (!Schema::hasTable('staff_accounts')) {
            return null;
        }

        $staffQuery = StaffAccount::query();
        foreach ($filtered as $key => $value) {
            $staffQuery->where($key, $value);
        }
        return $staffQuery->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (!isset($credentials['password'])) {
            return false;
        }

        return $this->hasher->check($credentials['password'], $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        if (!isset($credentials['password'])) {
            return;
        }

        if ($this->hasher->needsRehash($user->getAuthPassword()) || $force) {
            $user->forceFill([
                'password' => $this->hasher->make($credentials['password']),
            ])->save();
        }
    }
}

