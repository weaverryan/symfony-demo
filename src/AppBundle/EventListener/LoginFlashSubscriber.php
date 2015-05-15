<?php

namespace AppBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginFlashSubscriber implements EventSubscriberInterface
{
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var Session $session */
        $session = $event->getRequest()->getSession();

        $session->getFlashBag()->add('success', 'Yay! You just logged in!');
    }

    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin'
        ];
    }

}