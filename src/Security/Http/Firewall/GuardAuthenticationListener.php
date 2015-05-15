<?php

namespace Symfony\Component\Security\Http\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\NonAuthenticatedGuardToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\GuardAuthenticatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;
use Symfony\Component\Security\Http\SecurityEvents;

class GuardAuthenticationListener implements ListenerInterface
{
    private $tokenStorage;
    private $authenticationManager;
    private $providerKey;
    private $guardAuthenticator;
    private $logger;
    private $dispatcher;
    private $rememberMeServices;

    /**
     * Constructor.
     *
     * @param TokenStorageInterface           $tokenStorage          A TokenStorageInterface instance
     * @param AuthenticationManagerInterface  $authenticationManager An AuthenticationManagerInterface instance
     * @param string                          $providerKey
     * @param GuardAuthenticatorInterface     $guardAuthenticator    A GuardAuthenticatorInterface instance
     * @param LoggerInterface                 $logger                A LoggerInterface instance
     * @param EventDispatcherInterface        $dispatcher            An EventDispatcherInterface instance
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, $providerKey, GuardAuthenticatorInterface $guardAuthenticator, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null)
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->guardAuthenticator = $guardAuthenticator;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * This interface must be implemented by firewall listeners.
     *
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (null !== $this->logger) {
            $this->logger->info('Checking for guard authentication credentials', array('key' => $this->providerKey, 'authenticator' => get_class($this->guardAuthenticator)));
        }

        try {
            $credentials = $this->guardAuthenticator->getCredentialsFromRequest($request);
            $token = new NonAuthenticatedGuardToken($credentials, $this->providerKey);

            // allow null to be returned to skip authentication
            if (null === $token) {
                return;
            }

            $token = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($token);

            if (null !== $this->dispatcher) {
                $loginEvent = new InteractiveLoginEvent($request, $token);
                $this->dispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
            }
        } catch (AuthenticationException $e) {
            // oh no! Authentication failed! Time to tell the user!
            $this->tokenStorage->setToken(null);

            if (null !== $this->logger) {
                $this->logger->info('Guard authentication failed.', array('exception' => $e, 'authenticator' => get_class($this->guardAuthenticator)));
            }

            $response = $this->guardAuthenticator->onAuthenticationFailure($request, $e);
            if ($response instanceof Response) {
                $event->setResponse($response);
            } elseif (null !== $response) {
                // returning null is ok, it means they want the request to continue
                throw new \UnexpectedValueException(sprintf(
                    'The %s::onAuthenticationFailure method must return null or a Response object. You returned %s',
                    get_class($this->guardAuthenticator),
                    is_object($response) ? get_class($response) : gettype($response)
                ));
            }

            return;
        }

        // success!
        $response = $this->guardAuthenticator->onAuthenticationSuccess($request, $token, $this->providerKey);
        if ($response instanceof Response) {
            $event->setResponse($response);
        } elseif (null !== $response) {
            throw new \UnexpectedValueException(sprintf(
                'The %s::onAuthenticationSuccess method must return null or a Response object. You returned %s',
                get_class($this->guardAuthenticator),
                is_object($response) ? get_class($response) : gettype($response)
            ));
        }

        // if they have activated remember me, let's do it!
        if (null !== $this->rememberMeServices) {
            if (!$response instanceof Response) {
                throw new \LogicException(sprintf(
                    'onAuthenticationSuccess in %s *must* return a Response if you want to use the remember me functionality. Return a Response, or set remember_me to false under the guard configuration.',
                    get_class($this->guardAuthenticator)
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