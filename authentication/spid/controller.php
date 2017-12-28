<?php

namespace Concrete\Package\Spid\Authentication\Spid;

use Concrete\Core\Authentication\AuthenticationTypeController;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\Group\GroupList;
use Concrete\Core\User\User;
use SPID\Repository\IdentityProviderRepository;

class Controller extends AuthenticationTypeController
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeController::getHandle()
     */
    public function getHandle()
    {
        return 'spid';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeController::getAuthenticationTypeIconHTML()
     */
    public function getAuthenticationTypeIconHTML()
    {
        $path = REL_DIR_PACKAGES . '/spid/images';

        return '<img alt="SPID" src="' . $path . '/spid/spid-ico-circle-lb-20.svg" onerror="' . h('this.onerror = null; this.src = ' . json_encode($path . '/spid/spid-ico-circle-lb-20.png') . ';') . '" />';
    }

    /**
     * Method called when the type form is shown.
     */
    public function edit()
    {
        $this->requireAsset('spid/login');
        $this->set('urlResolver', $this->app->make(ResolverManagerInterface::class));
        $groupList = $this->app->make(GroupList::class);
        $groupList->includeAllGroups();
        $this->set('groups', $groupList->getResults());
        $config = $this->app->make('spid/config');
        $this->set('registrationEnabled', (bool) $config->get('registration.enabled'));
        $this->set('registrationGroupId', (int) $config->get('registration.groupId') ?: null);
    }

    /**
     * Method called when the type form is saved.
     *
     * @param mixed $args
     */
    public function saveAuthenticationType($args)
    {
        if (!is_array($args)) {
            $args = [];
        }
        $args += [
            'registrationEnabled' => false,
            'registrationGroupId' => null,
        ];
        $config = $this->app->make('spid/config');
        $config->save('registration.enabled', (bool) $args['registrationEnabled']);
        $config->save('registration.groupId', (int) $args['registrationGroupId'] ?: null);
    }

    /**
     * Method called when the login form is shown.
     */
    public function form()
    {
        $this->requireAsset('spid/login');
        $repo = $this->app->make(IdentityProviderRepository::class);
        $this->set('urlResolver', $this->app->make(ResolverManagerInterface::class));
        $this->set('identityProviders', $repo->getIdentityProviders());
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeControllerInterface::verifyHash()
     */
    public function verifyHash(User $u, $hash)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeController::view()
     */
    public function view()
    {
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeControllerInterface::authenticate()
     */
    public function authenticate()
    {
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeControllerInterface::buildHash()
     */
    public function buildHash(User $u)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeControllerInterface::isAuthenticated()
     */
    public function isAuthenticated(User $u)
    {
        return $u->isRegistered() && $u->isActive();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Authentication\AuthenticationTypeControllerInterface::deauthenticate()
     */
    public function deauthenticate(User $u)
    {
    }
}
