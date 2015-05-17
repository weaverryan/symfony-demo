<?php

namespace AppBundle\Security;

use AppBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    // UserProviderInterface
    public function loadUserByUsername($username)
    {
        $user = $this->getUserRepository()->findOneBy(array('username' => $username));

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }

    // UserProviderInterface
    public function refreshUser(UserInterface $user)
    {
        $user = $this->getUserRepository()->find($user->getId());
        if (!$user) {
            throw new UsernameNotFoundException(sprintf('User with id "%s" not found!', $user->getId()));
        }

        return $user;
    }

    // UserProviderInterface
    public function supportsClass($class)
    {
        return $class === get_class($this) || is_subclass_of($class, get_class($this));
    }

    // our own custom method
    public function loadUserByToken($token)
    {
        return $this->getUserRepository()->findOneBy(array(
            'token' => $token
        ));
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->em->getRepository('AppBundle:User');
    }
}
