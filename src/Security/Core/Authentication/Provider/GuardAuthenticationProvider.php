<?php

namespace Symfony\Component\Security\Core\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\GuardAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\NonAuthenticatedGuardToken;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class GuardAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var GuardAuthenticatorInterface[]
     */
    private $guardAuthenticators;
    private $userProvider;
    private $providerKey;
    private $userChecker;

    public function __construct(array $guardAuthenticators, UserProviderInterface $userProvider, $providerKey, UserCheckerInterface $userChecker)
    {
        $this->guardAuthenticators = $guardAuthenticators;
        $this->userProvider = $userProvider;
        $this->providerKey = $providerKey;
        $this->userChecker = $userChecker;
    }

    /**
     * @param NonAuthenticatedGuardToken $token
     * @return TokenInterface
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$token instanceof NonAuthenticatedGuardToken) {
            throw new \InvalidArgumentException('GuardAuthenticationProvider only supports NonAuthenticatedGuardToken');
        }

        // find the *one* GuardAuthenticator that this token originated from
        foreach ($this->guardAuthenticators as $key => $guardAuthenticator) {
            // get a key that's unique to *this* guard authenticator
            // this MUST be the same as GuardAuthenticationListener
            $uniqueGuardKey = $this->providerKey.'_'.$key;

            if ($uniqueGuardKey == $token->getGuardProviderKey()) {
                return $this->authenticateViaGuard($guardAuthenticator, $token);
            }
        }

        throw new \LogicException(sprintf(
            'The correct GuardAuthenticator could not be found for unique key "%s". The listener and provider should be passed the same list of authenticators!?',
            $token->getGuardProviderKey()
        ));
    }

    private function authenticateViaGuard(GuardAuthenticatorInterface $guardAuthenticator, NonAuthenticatedGuardToken $token)
    {
        $user = $guardAuthenticator->authenticate($token->getCredentials(), $this->userProvider);

        if (!$user instanceof UserInterface) {
            throw new \UnexpectedValueException(sprintf(
                'The %s::authenticate method must return a UserInterface. You returned %s',
                get_class($guardAuthenticator),
                is_object($user) ? get_class($user) : gettype($user)
            ));
        }

        // check the AdvancedUserInterface methods!
        $this->userChecker->checkPreAuth($user);;
        $this->userChecker->checkPostAuth($user);

        $token = $guardAuthenticator->createAuthenticatedToken($user, $this->providerKey);
        if (!$token instanceof TokenInterface) {
            throw new \UnexpectedValueException(sprintf(
                'The %s::createAuthenticatedToken method must return a TokenInterface. You returned %s',
                get_class($guardAuthenticator),
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
