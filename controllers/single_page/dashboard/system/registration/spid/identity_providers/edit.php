<?php

namespace Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid\IdentityProviders;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Url\Resolver\Manager\ResolverManager;
use SPID\Attributes\SpidAttributes;
use SPID\Entity\IdentityProvider;
use SPID\IdentityProviderFactory;

defined('C5_EXECUTE') or die('Access Denied.');

class Edit extends DashboardPageController
{
    public function view($id = '')
    {
        $repo = $this->entityManager->getRepository(IdentityProvider::class);
        $identityProvider = $id ? $this->entityManager->find(IdentityProvider::class, $id) : null;
        if ($identityProvider === null) {
            $this->flash('error', t('Unable to find the specified identity provider.'));
            $rm = $this->app->make(ResolverManager::class);

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $rm->resolve(['/dashboard/system/registration/spid/identity_providers']),
                302
            );
        }
        $this->set('identityProvider', $identityProvider);
        $this->set('dateHelper', $this->app->make('date'));
        $this->set('spidAttributes', $this->app->make(SpidAttributes::class));
    }

    public function save($id = '')
    {
        $identityProvider = $id ? $this->entityManager->find(IdentityProvider::class, $id) : null;
        if ($identityProvider === null) {
            $this->flash('error', t('Unable to find the specified identity provider.'));
            $rm = $this->app->make(ResolverManager::class);

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $rm->resolve(['/dashboard/system/registration/spid/identity_providers']),
                302
            );
        }
        if (!$this->token->validate('spid-idp-edit-' . $identityProvider->getIdentityProviderRecordId())) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $post = $this->request->request;
            $repo = $this->entityManager->getRepository(IdentityProvider::class);
            $name = $post->get('name');
            $name = is_string($name) ? trim($name) : '';
            if ($name === '') {
                $this->error->add(t('Please specify the Identity Provider name.'));
            } else {
                $qb = $repo->createQueryBuilder('ip');
                $other = $qb
                    ->where($qb->expr()->eq('ip.ipName', ':name'))->setParameter('name', $identityProvider->getIdentityProviderName())
                    ->andWhere($qb->expr()->neq('ip.ipRecordId', ':id'))->setParameter('id', $identityProvider->getIdentityProviderRecordId())
                    ->setMaxResults(1)
                    ->getQuery()->getOneOrNullResult()
                ;
                if ($other !== null) {
                    $this->error->add(t('There\'s already another Identity Provider named "%s".', $name));
                }
            }
            $metadataUrl = '';
            $metadataXml = '';
            if ($identityProvider->getIdentityProviderMetadataUrl() !== '') {
                $metadataUrl = $post->get('metadataUrl');
                $metadataUrl = is_string($metadataUrl) ? trim($metadataUrl) : '';
                if ($metadataUrl === '') {
                    $this->error->add(t('Please specify the URL of the Identity Provider metadata.'));
                }
                $unreliableSsl = !empty($post->get('unreliableSsl'));
            } else {
                $metadataXml = $post->get('metadataXml');
                $metadataXml = is_string($metadataXml) ? trim($metadataXml) : '';
            }
            if (!$this->error->has()) {
                $factory = $this->app->make(IdentityProviderFactory::class);
                try {
                    if ($metadataUrl !== '') {
                        $factory->getIdentityProviderFromMetadataUrl($metadataUrl, $identityProvider, $unreliableSsl);
                    } elseif ($metadataXml !== '') {
                        $factory->getIdentityProviderFromMetadataString($metadataXml, $identityProvider);
                    }
                } catch (UserMessageException $x) {
                    $this->error->add($x);
                }
                if (!$this->error->has()) {
                    $identityProvider->setIdentityProviderName($name);
                    $identityProvider->setIdentityProviderMetadataUrl($metadataUrl);
                    $this->entityManager->flush($identityProvider);
                }
            }
        }
        if ($this->error->has()) {
            $this->view($identityProvider->getIdentityProviderRecordId());
        } else {
            $this->flash('success', t('The identity provider has been updated.'));
            $rm = $this->app->make(ResolverManager::class);

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $rm->resolve(['/dashboard/system/registration/spid/identity_providers']),
                302
            );
        }
    }

    public function delete($id = '')
    {
        $identityProvider = $id ? $this->entityManager->find(IdentityProvider::class, $id) : null;
        if ($identityProvider === null) {
            $this->flash('error', t('Unable to find the specified identity provider.'));
            $rm = $this->app->make(ResolverManager::class);

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $rm->resolve(['/dashboard/system/registration/spid/identity_providers']),
                302
                );
        }
        if (!$this->token->validate('spid-idp-delete-' . $identityProvider->getIdentityProviderRecordId())) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $this->entityManager->remove($identityProvider);
            $this->entityManager->flush($identityProvider);
        }
        if ($this->error->has()) {
            $this->view($identityProvider->getIdentityProviderRecordId());
        } else {
            $this->flash('success', t('The identity provider has been deleted.'));
            $rm = $this->app->make(ResolverManager::class);

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $rm->resolve(['/dashboard/system/registration/spid/identity_providers']),
                302
                );
        }
    }
}
