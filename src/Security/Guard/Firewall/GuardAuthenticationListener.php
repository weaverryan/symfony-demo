<?php

namespace Symfony\Component\Security\Guard\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Guard\Token\NonAuthenticatedGuardToken;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
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

            if (null !== $this->logger) {
                $this->logger->info('Guard authentication successful!', array('token' => $token, 'authenticator' => get_class($guardAuthenticator)));
            }

            $this->guardHandler->authenticateWithToken($token, $request);
        } catch (AuthenticationException $e) {
            // oh no! Authentication failed!

            if (null !== $this->logger) {
                $this->logger->info('Guard authentication failed.', array('exception' => $e, 'authenticator' => get_class($guardAuthenticator)));
            }

            $response = $this->guardHandler->handleAuthenticationFailure($e, $request, $guardAuthenticator);

            if ($response instanceof Response) {
                $event->setResponse($response);
            }

            return;
        }

        // success!
        $response = $this->guardHandler->handleAuthenticationSuccess($token, $request, $guardAuthenticator, $this->providerKey);
        if ($response instanceof Response) {
            if (null !== $this->logger) {
                $this->logger->info('Guard authenticator set success response', array('response' => $response, 'authenticator' => get_class($guardAuthenticator)));
            }

            $event->setResponse($response);
        } else {
            if (null !== $this->logger) {
                $this->logger->info('Guard authenticator set no success response: request continues', array('authenticator' => get_class($guardAuthenticator)));
            }
        }

        // attempt to trigger the remember me functionality
        $this->triggerRememberMe($guardAuthenticator, $request, $token, $response);
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

    /**
     * Checks to see if remember me is supported in the authenticator and
     * on the firewall. If it is, the RememberMeServicesInterface is notified
     *
     * @param GuardAuthenticatorInterface $guardAuthenticator
     * @param Request $request
     * @param TokenInterface $token
     * @param Response $response
     */
    private function triggerRememberMe(GuardAuthenticatorInterface $guardAuthenticator, Request $request, TokenInterface $token, Response $response = null)
    {
        if (!$guardAuthenticator->supportsRememberMe()) {
            return;
        }

        if (null === $this->rememberMeServices) {
            if (null !== $this->logger) {
                $this->logger->info('Remember me skipped: it is not configured for the firewall', array('authenticator' => get_class($guardAuthenticator)));
            }

            return;
        }

        if (!$response instanceof Response) {
            throw new \LogicException(sprintf(
                '%s::onAuthenticationSuccess *must* return a Response if you want to use the remember me functionality. Return a Response, or set remember_me to false under the guard configuration.',
                get_class($guardAuthenticator)
            ));
        }

        $this->rememberMeServices->loginSuccess($request, $response, $token);
    }
}