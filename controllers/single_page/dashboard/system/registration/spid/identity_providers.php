<?php

namespace Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use SPID\Entity\IdentityProvider;
use SPID\IdentityProviderFactory;
use SPID\Repository\IdentityProviderRepository;

defined('C5_EXECUTE') or die('Access Denied.');

class IdentityProviders extends DashboardPageController
{
    public function view()
    {
        $this->addHeaderItem(<<<EOT
<style>
.spid-action-cell {
    width: 1px;
}
.spid-action-cell, .spid-action-cell a {
    white-space: nowrap;
}
.spid-logo-cell {
    padding-left: 8px;
    text-align: center;
    width: 100px;
}
td.spid-sort-cell {
    text-align: right; 
}
td.spid-sort-cell i {
    cursor: move;
}
img.spid-idp-logo {
    max-height: 15px;
    max-width: 80px;
}
</style>
EOT
        );
        $this->set('dateHelper', $this->app->make('date'));
        $repo = $this->app->make(IdentityProviderRepository::class);
        $this->set('identityProviders', $repo->getIdentityProviders(true));
        $repo = $this->entityManager->getRepository(IdentityProvider::class);
        $factory = $this->app->make(IdentityProviderFactory::class);
        $missingDefaultIdentityProviders = [];
        foreach ($factory->getDefaultIdentityProviderMetadataUrls() as $name => list($metadata, $icon)) {
            if ($repo->findOneBy(['ipMetadataUrl' => $metadata]) === null) {
                $missingDefaultIdentityProviders[] = [
                    'name' => $name,
                    'metadata' => $metadata,
                ];
            }
        }
        $this->set('missingDefaultIdentityProviders', $missingDefaultIdentityProviders);
    }

    public function enable_idp($id = '', $enable = '', $token = '')
    {
        if (!$this->token->validate("spid-idp-enable-{$enable}-{$id}", $token)) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $identityProvider = $id ? $this->entityManager->find(IdentityProvider::class, $id) : null;
            if ($identityProvider === null) {
                $this->error->add(t('Unable to find the specified identity provider.'));
            } else {
                $identityProvider->setIdentityProviderEnabled($enable);
                $this->entityManager->flush($identityProvider);
                $this->set(
                    'success',
                    $identityProvider->isIdentityProviderEnabled() ?
                        t('The identity provider %s has been enabled.', $identityProvider->getIdentityProviderDisplayName())
                        :
                        t('The identity provider %s has been disabled.', $identityProvider->getIdentityProviderDisplayName())
                );
            }
        }
        $this->view();
    }

    public function reorder_idp()
    {
        if (!$this->token->validate('spid-idp-sort')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $order = $this->request->request->get('order');
        if (!is_array($order)) {
            throw new UserMessageException(t('Invalid parameter received: %s', 'order'));
        }
        $order = array_values(array_map('intval', $order));
        $repo = $this->entityManager->getRepository(IdentityProvider::class);
        foreach ($repo->findAll() as $identityProvider) {
            $index = array_search($identityProvider->getIdentityProviderRecordId(), $order, true);
            if ($index === false) {
                throw new UserMessageException(t('Invalid parameter received: %s', 'order'));
            }
            $identityProvider->setIdentityProviderSort($index);
            unset($order[$index]);
        }
        if (count($order) !== 0) {
            throw new UserMessageException(t('Invalid parameter received: %s', 'order'));
        }
        $this->entityManager->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function create_idp()
    {
        $errors = $this->app->make('error');
        if (!$this->token->validate('spid-idp-create')) {
            $errors->add($this->token->getErrorMessage());
        } else {
            $repo = $this->app->make(IdentityProviderRepository::class);
            $post = $this->request->request;
            $name = $post->get('spid_idpcreate_name');
            $name = is_string($name) ? trim($name) : '';
            if ($name === '') {
                $errors->add(t('Please specify the Identity Provider name.'));
            } else {
                if ($repo->findOneBy(['ipName' => $name]) !== null) {
                    $errors->add(t('There\'s already another Identity Provider named "%s".', $name));
                }
            }
            $metadataUrl = '';
            $metadataXml = '';
            switch ($post->get('spid_idpcreate_metadata_kind')) {
                case 'url':
                    $metadataUrl = $post->get('spid_idpcreate_metadata_url');
                    $metadataUrl = is_string($metadataUrl) ? trim($metadataUrl) : '';
                    if ($metadataUrl === '') {
                        $errors->add(t('Please specify the URL of the Identity Provider metadata.'));
                    }
                    break;
                case 'xml':
                    $metadataXml = $post->get('spid_idpcreate_metadata_xml');
                    $metadataXml = is_string($metadataXml) ? trim($metadataXml) : '';
                    if ($metadataXml === '') {
                        $errors->add(t('Please specify the XML of the Identity Provider metadata.'));
                    }
                    break;
                default:
                    throw new UserMessageException(t('Invalid parameter received: %s', 'spid_idpcreate_metadata_kind'));
            }
            if (!$errors->has()) {
                $factory = $this->app->make(IdentityProviderFactory::class);
                try {
                    if ($metadataUrl !== '') {
                        $identityProvider = $factory->getIdentityProviderFromMetadataUrl($metadataUrl);
                    } else {
                        $identityProvider = $factory->getIdentityProviderFromMetadataString($metadataXml);
                    }
                } catch (UserMessageException $x) {
                    $errors->add($x);
                }
                if (!$errors->has()) {
                    $identityProvider
                        ->setIdentityProviderName($name)
                        ->setIdentityProviderSort($repo->getNextIdentityProviderSort())
                        ->setIdentityProviderMetadataUrl($metadataUrl)
                    ;
                    foreach ($this->app->make(IdentityProviderFactory::class)->getDefaultIdentityProviderMetadataUrls() as list($defaultMetadata, $defaultIcon)) {
                        if ($defaultMetadata === $metadataUrl) {
                            $identityProvider->setIdentityProviderIcon($defaultIcon);
                            break;
                        }
                    }
                    $this->entityManager->persist($identityProvider);
                    $this->entityManager->flush($identityProvider);
                }
            }
        }
        if ($errors->has()) {
            $this->set('addDialogErrors', $errors);
            $this->set('showAddDialog', true);
            $this->view();
        } else {
            $this->flash('success', t('The new Identity Provider has been created.'));

            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $this->action(''),
                302
            );
        }
    }
}
