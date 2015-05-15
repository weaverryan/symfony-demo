<?php

namespace Symfony\Component\Security\Core\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\GuardAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\NonAuthenticatedGuardToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class GuardAuthenticationProvider implements AuthenticationProviderInterface
{
    private $guardAuthenticator;
    private $userProvider;
    private $providerKey;

    public function __construct(GuardAuthenticatorInterface $guardAuthenticator, UserProviderInterface $userProvider, $providerKey)
    {
        $this->guardAuthenticator = $guardAuthenticator;
        $this->userProvider = $userProvider;
        $this->providerKey = $providerKey;
    }

    /**
     * @param NonAuthenticatedGuardToken $token
     * @return TokenInterface
     */
    public function authenticate(TokenInterface $token)
    {
        $user = $this->guardAuthenticator
            ->authenticate($token->getCredentials(), $this->userProvider);

        if (!$user instanceof UserInterface) {
            throw new \UnexpectedValueException(sprintf(
                'The %s::authenticate method must return a UserInterface. You returned %s',
                get_class($this->guardAuthenticator),
                is_object($user) ? get_class($user) : gettype($user)
            ));
        }

        $token = $this->guardAuthenticator->createAuthenticatedToken($user, $this->providerKey);
        if (!$token instanceof TokenInterface) {
            throw new \UnexpectedValueException(sprintf(
                'The %s::createAuthenticatedToken method must return a TokenInterface. You returned %s',
                get_class($this->guardAuthenticator),
                is_object($token) ? get_class($token) : gettype($token)
            ));
        }

        return $token;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof NonAuthenticatedGuardToken;
    }
}