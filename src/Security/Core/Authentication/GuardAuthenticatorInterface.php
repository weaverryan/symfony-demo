<?php

namespace Symfony\Component\Security\Core\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

interface GuardAuthenticatorInterface
{
    /**
     * Get the authentication credentials from the request and return them
     * as an array. If you return null, authentication will be skipped.
     *
     * For example, for a form login, you might:
     *
     *      return array(
     *          'username' => $request->request->get('_username'),
     *          'password' => $request->request->get('_password'),
     *      );
     *
     * Or for an API token that's on a header, you might use:
     *
     *      return array('api_key' => $request->headers->get('X-API-TOKEN'));
     *
     * @param Request $request
     * @return array|null
     */
    public function getCredentialsFromRequest(Request $request);

    /**
     * Given an array of credentials, return a UserInterface or throw an
     * AuthenticationException on a failure
     *
     * @param array $credentials
     * @param UserProviderInterface $userProvider
     * @throws AuthenticationException
     * @return UserInterface
     */
    public function authenticate(array $credentials, UserProviderInterface $userProvider);

    /**
     * @param UserInterface $user
     * @param string $providerKey The firewall provider key
     * @return TokenInterface
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey);

    /**
     * Called when authentication executed, but failed (e.g. wrong username password)
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception);

    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param $providerKey
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey);
}
