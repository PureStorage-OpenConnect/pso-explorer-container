<?php

namespace App\Providers;

use App\PsoUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class FilePasswordProvider implements UserProvider
{
    /**
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        // Get and return a user by their unique identifier
        $user = new PsoUser([
            'name' => $identifier,
        ]);

        return $user;
    }

    /**
     * Get and return a user by their unique identifier and "remember me" token
     *
     * @param  mixed   $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // We will not support the remember me token
        return null;
    }

    /**
     * Save the given "remember me" token for the given user
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // We will not support the remember me token
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        try {
            // Create a new PsoUser with the credentials provided
            $user = new PsoUser($credentials);
        } catch (\ErrorException $e) {
            // If the user does not exist or the 'username' file does not exist an error will be thrown
            unset($e);
            return null;
        }

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Check that given credentials belong to the given user
        return Hash::check($credentials['password'], $user->getAuthPassword());
    }
}
