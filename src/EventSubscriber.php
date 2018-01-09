<?php

namespace SPID;

use Concrete\Core\Application\Application;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\User\Event\UserInfo as UserInfoEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    /**
     * The application object.
     *
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {inheritdoc}.
     *
     * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents
     */
    public static function getSubscribedEvents()
    {
        return [
            'on_user_deleted' => 'userDeleted',
        ];
    }

    /**
     * @param \Concrete\Core\User\Event\UserInfo $evt
     */
    public function userDeleted(UserInfoEvent $evt)
    {
        $uID = $evt->getUserInfoObject()->getUserID();
        $db = $this->app->make(Connection::class);
        $db->executeQuery('delete from SpidUsers where uID = ?', [$uID]);
    }
}
