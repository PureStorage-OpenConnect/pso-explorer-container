<?php

namespace App;

use ErrorException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PsoUser extends Authenticatable
{
    protected $fillable = [
        'email', 'name', 'password',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Try to read the username from the mounted secret, this might cause an
        // ErrorException if the file does not exist. We will however not catch that
        // since in that case the Authentication Provider needs to catch the error
        // and treat it as an unautorized request
        $username = file_get_contents('/etc/pso-explorer/username');

        // Remove any spaces or new lines from password hash
        $username = trim(preg_replace('/\s\s+/', '', $username));

        if ($username !== $attributes['name']) {
            // If this is not the correct username, throw an error
            throw new ErrorException();
        }
    }

    /**
     * Return the name of unique identifier for the user (e.g. "id").
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        // We'll use 'username' as user identified
        return 'name';
    }

    /**
     * Return the unique identifier for the user (e.g. their ID, 123).
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        // For now (until we support multiple users) we'll just return a fixed user id
        return $this->attributes['name'];
    }

    /**
     * Returns the (hashed) password for the user by reading if from the password file.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        // Try to read the password from the mounted secret
        try {
            $password = file_get_contents('/etc/pso-explorer/password');
        } catch (ErrorException $e) {
            unset($e);
            return false;
        }

        // Remove any spaces or new lines from password hash
        $password = trim(preg_replace('/\s\s+/', '', $password));

        // Return the password hash
        return $password;
    }

    /**
     * Return the token used for the "remember me" functionality.
     *
     * @return string
     */
    public function getRememberToken()
    {
        // We currently don't support a RememberToken
        return null;
    }

    /**
     * Store a new token user for the "remember me" functionality.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        // We currently don't support a RememberToken
    }

    /**
     * Return the name of the column / attribute used to store the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        // We currently don't support a RememberToken, so we'll return a fake column name for now
        return 'remember_token';
    }
}
