<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Example authenticator that reads from a header
 */
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    public function getCredentialsFromRequest(Request $request)
    {
        $token = $request->headers->get('X-AUTH-TOKEN');

        // no username? Don't do anything :D
        if (!$token) {
            return;
        }

        return [
            'token' => $token,
        ];
    }

    public function authenticate($credentials, UserProviderInterface $userProvider)
    {
        $token = $credentials['token'];

        // use pretending like the token is a real token
        $user = $userProvider->loadUserByToken($token);

        if (!$user) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, just let the request keep going!
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            // todo - I might translate this
            'message' => $exception->getMessageKey()
        );

        return new Response(json_encode($data), 403);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            // todo - I might translate this
            'message' => 'Authentication Required'
        );

        return new Response(json_encode($data), 401);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
