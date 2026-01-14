<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

/**
 * Custom user provider that only allows UUIDs as identifiers.
 *
 * This prevents using integer IDs which are not in use in this application.
 * This is needed because, with UUID ids for models, an old cookie with an integer id will cause a 500 error.
 */
class StringIdUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     * This is called by the guard for normal login or session retrieval.
     *
     * @param mixed $identifier
     * @return Authenticatable|null
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return Str::isUuid($identifier) ? parent::retrieveById($identifier) : null;

    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     * This is called during "remember me" cookie login.
     *
     * @param mixed $identifier
     * @param string $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return Str::isUuid($identifier) ? parent::retrieveByToken($identifier, $token) : null;
    }
}
