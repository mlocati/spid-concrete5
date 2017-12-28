<?php

namespace Concrete\Package\Spid\Controller\Frontend;

use Concrete\Core\Controller\Controller;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Url\Resolver\Manager\ResolverManager;
use SPID\Repository\IdentityProviderRepository;
use SPID\Saml;
use SPID\User;

class Spid extends Controller
{
    /**
     * @param int $identityProviderRecordId
     *
     * @return \Concrete\Core\Http\SymfonyResponse
     */
    public function startLogin($identityProviderRecordId)
    {
        $redirectUrl = null;
        $repo = $this->app->make(IdentityProviderRepository::class);
        $identityProvider = $identityProviderRecordId ? $repo->findOneBy(['ipRecordId' => (int) $identityProviderRecordId, 'ipEnabled' => true]) : null;
        if ($identityProvider !== null) {
            $saml = $this->app->make(Saml::class);
            $redirectUrl = $saml->getLoginUrl($identityProvider);
        }
        if ($redirectUrl === null) {
            $redirectUrl = $this->app->make(ResolverManager::class)->resolve(['/']);
        }

        return $this->app->make(ResponseFactoryInterface::class)->redirect($redirectUrl, 302);
    }

    /**
     * @return array|null
     */
    public function assertion()
    {
        $redirectUrl = null;
        $saml = $this->app->make(Saml::class);
        $relayState = $saml->parseRelayState();
        if ($relayState !== null) {
            list($identityProvider, $url) = $relayState;
            $attributes = $saml->handleAssertion($identityProvider);
            if ($attributes !== null) {
                $user = $this->app->make(User::class)->loginByAttributes($attributes);
            }
        }

        if ($redirectUrl === null) {
            $redirectUrl = $this->app->make(ResolverManager::class)->resolve(['/']);
        }

        return $this->app->make(ResponseFactoryInterface::class)->redirect($redirectUrl, 302);
    }
}
