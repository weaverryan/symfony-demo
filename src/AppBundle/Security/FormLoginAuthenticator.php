<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\GuardAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorProviderAwareInterface;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Security;

class FormLoginAuthenticator implements GuardAuthenticatorInterface
{
    private $passwordEncoder;

    private $router;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, RouterInterface $router)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->router = $router;
    }

    public function getCredentialsFromRequest(Request $request)
    {
        if ($request->getPathInfo() != '/login_check' || !$request->isMethod('POST')) {
            return;
        }

        $username = $request->request->get('_username');
        $password = $request->request->get('_password');

        return [
            'username' => $username,
            'password' => $password
        ];
    }

    public function authenticate($credentials, UserProviderInterface $userProvider)
    {
        $username = $credentials['username'];
        $password = $credentials['password'];
        $user = $userProvider->loadUserByUsername(
            $username
        );

        if (!$user) {
            throw new UsernameNotFoundException();
        }

        $passwordValid = $this->passwordEncoder->isPasswordValid($user, $password);
        if (!$passwordValid) {
            throw new BadCredentialsException();
        }

        return $user;
    }

    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        return new UsernamePasswordToken(
            $user,
            $user->getPassword(),
            $providerKey,
            $user->getRoles()
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetPath = $request->getSession()->get('_security.'.$providerKey.'.target_path');

        if (!$targetPath) {
            $targetPath = $this->router->generate('homepage');
        }

        return new RedirectResponse($targetPath);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('security_login_form'));
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse($this->router->generate('security_login_form'));
    }
}
