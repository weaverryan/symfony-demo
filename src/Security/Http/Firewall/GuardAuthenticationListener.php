<?php

namespace Symfony\Component\Security\Http\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\GuardAuthenticatorHandler;
use Symfony\Component\Security\Core\Authentication\Token\NonAuthenticatedGuardToken;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\GuardAuthenticatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class GuardAuthenticationListener implements ListenerInterface
{
    private $guardHandler;
    private $authenticationManager;
    private $providerKey;
    private $guardAuthenticators;
    private $logger;
    private $rememberMeServices;

    /**
     * Constructor.
     *
     * @param GuardAuthenticatorHandler       $guardHandler          The Guard handler
     * @param AuthenticationManagerInterface  $authenticationManager An AuthenticationManagerInterface instance
     * @param string                          $providerKey
     * @param GuardAuthenticatorInterface[]   $guardAuthenticators   The GuardAuthenticatorInterface instances
     * @param LoggerInterface                 $logger                A LoggerInterface instance
     */
    public function __construct(GuardAuthenticatorHandler $guardHandler, AuthenticationManagerInterface $authenticationManager, $providerKey, $guardAuthenticators, LoggerInterface $logger = null)
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->guardHandler = $guardHandler;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->guardAuthenticators = $guardAuthenticators;
        $this->logger = $logger;
    }

    /**
     * This interface must be implemented by firewall listeners.
     *
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        if (null !== $this->logger) {
            $this->logger->info('Checking for guard authentication credentials', array('key' => $this->providerKey, 'authenticators' => count($this->guardAuthenticators)));
        }

        foreach ($this->guardAuthenticators as $key => $guardAuthenticator) {
            // get a key that's unique to *this* guard authenticator
            // this MUST be the same as GuardAuthenticationProvider
            $uniqueGuardKey = $this->providerKey.'_'.$key;

            $this->executeGuardAuthenticator($uniqueGuardKey, $guardAuthenticator, $event);
        }
    }

    private function executeGuardAuthenticator($uniqueGuardKey, GuardAuthenticatorInterface $guardAuthenticator, GetResponseEvent $event)
    {
        $request = $event->getRequest();
        try {
            if (null !== $this->logger) {
                $this->logger->info('Calling getCredentialsFromRequest on guard configurator', array('key' => $this->providerKey, 'authenticator' => get_class($guardAuthenticator)));
            }

            $credentials = $guardAuthenticator->getCredentialsFromRequest($request);

            // allow null to be returned to skip authentication
            if (null === $credentials) {
                return;
            }

            // create a token with the unique key, so that the provider knows which authenticator to use
            $token = new NonAuthenticatedGuardToken($credentials, $uniqueGuardKey);

            if (null !== $this->logger) {
                $this->logger->info('Passing guard token information to the GuardAuthenticationProvider', array('key' => $this->providerKey, 'authenticator' => get_class($guardAuthenticator)));
            }
            $token = $this->authenticationManager->authenticate($token);

            $this->guardHandler->authenticateWithToken($token, $request);
        } catch (AuthenticationException $e) {
            // oh no! Authentication failed!
            $response = $this->guardHandler->handleAuthenticationFailure($guardAuthenticator, $e, $request);

            if ($response instanceof Response) {
                $event->setResponse($response);
            }

            return;
        }

        // success!
        $response = $this->guardHandler->handleAuthenticationSuccess($guardAuthenticator, $request, $token, $this->providerKey);
        if ($response instanceof Response) {
            $event->setResponse($response);
        }

        // if they have activated remember me, let's do it!
        if (null !== $this->rememberMeServices && $guardAuthenticator->supportsRememberMe()) {
            if (!$response instanceof Response) {
                throw new \LogicException(sprintf(
                    '%s::onAuthenticationSuccess *must* return a Response if you want to use the remember me functionality. Return a Response, or set remember_me to false under the guard configuration.',
                    get_class($guardAuthenticator)
                ));
            }

            $this->rememberMeServices->loginSuccess($request, $response, $token);
        }
    }

    /**
     * Should be called if this listener will support remember me.
     *
     * @param RememberMeServicesInterface $rememberMeServices
     */
    public function setRememberMeServices(RememberMeServicesInterface $rememberMeServices)
    {
        $this->rememberMeServices = $rememberMeServices;
    }
}