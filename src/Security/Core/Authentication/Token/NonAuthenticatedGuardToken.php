<?php

namespace Symfony\Component\Security\Core\Authentication\Token;

/**
 * The token used by the guard auth system before authentication
 *
 * The GuardAuthenticationListener creates this, which is then consumed
 * immediately by the GuardAuthenticationProvider. If authentication is
 * successful, a different authenticated token is returned
 */
class NonAuthenticatedGuardToken extends AbstractToken
{
    private $credentials;
    private $providerKey;

    public function __construct($credentials, $providerKey)
    {
        $this->credentials = $credentials;
        $this->providerKey = $providerKey;

        parent::__construct(array());
    }

    /**
     * Returns the user credentials, which might be an array of anything you
     * wanted to put in there (e.g. username, password, favoriteColor).
     *
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    public function isAuthenticated()
    {
        return false;
    }

    public function setAuthenticated($authenticated)
    {
        throw new \Exception('The NonAuthenticatedGuardToken is *always* not authenticated');
    }
}
