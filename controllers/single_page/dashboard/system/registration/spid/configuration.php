<?php

namespace Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use SPID\AuthenticationLevel;
use SPID\PEM;

defined('C5_EXECUTE') or die('Access Denied.');

class Configuration extends DashboardPageController
{
    public function view()
    {
        $config = $this->app->make('config');
        $pem = $this->app->make(PEM::class);
        $this->set('entityId', (string) $config->get('spid::service_provider.entityId'));
        $this->set('signingPrivateKey', $pem->format($config->get('spid::service_provider.signing.privateKey'), PEM::KIND_PRIVATEKEY));
        $this->set('signingX509certificate', $pem->format($config->get('spid::service_provider.signing.x509certificate'), PEM::KIND_X509CERTIFICATE));
        $authenticationLevels = $this->app->make(AuthenticationLevel::class)->getLevels();
        $authenticationLevel = (string) $config->get('spid::service_provider.authenticationLevel');
        if (!isset($authenticationLevels[$authenticationLevel])) {
            $authenticationLevel = AuthenticationLevel::DEFAULT_LEVEL;
        }
        $this->set('authenticationLevels', $authenticationLevels);
        $this->set('authenticationLevel', $authenticationLevel);
        $this->set('checkSignatures', (bool) $config->get('spid::service_provider.checkSignatures'));
        $this->set('wantMessagesSigned', (bool) $config->get('spid::service_provider.wantMessagesSigned'));
        $this->set('logMessages', (bool) $config->get('spid::service_provider.logMessages'));
    }

    public function save()
    {
        if (!$this->token->validate('spid-configuration-save')) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $pem = $this->app->make(PEM::class);
            $post = $this->request->request;
            $entityId = $post->get('entityId');
            $entityId = is_string($entityId) ? trim($entityId) : '';
            if ($entityId === '') {
                $this->error->add('Please specify the entity ID.');
            }
            $signingPrivateKey = $pem->simplify($post->get('signingPrivateKey'));
            if ($signingPrivateKey === '') {
                $this->error->add('Please specify the private key.');
            }
            $signingX509certificate = $pem->simplify($post->get('signingX509certificate'));
            if ($signingX509certificate === '') {
                $this->error->add('Please specify the X.509 certificate.');
            }
            if ($signingPrivateKey !== '' && $signingX509certificate !== '') {
                $this->error->add($this->checkSigning($pem->format($signingPrivateKey, PEM::KIND_PRIVATEKEY), $pem->format($signingX509certificate, PEM::KIND_X509CERTIFICATE), $this->error));
            }
            $authenticationLevels = $this->app->make(AuthenticationLevel::class)->getLevels();
            $authenticationLevel = $post->get('authenticationLevel');
            if (!is_string($authenticationLevel) || !isset($authenticationLevels[$authenticationLevel])) {
                $this->error->add('Please specify the authentication level.');
            }
            $checkSignatures = (bool) $post->get('checkSignatures');
            $wantMessagesSigned = (bool) $post->get('wantMessagesSigned');
            $logMessages = (bool) $post->get('logMessages');
        }
        if ($this->error->has()) {
            $this->view();
        } else {
            $config = $this->app->make('config');
            $config->save('spid::service_provider.entityId', $entityId);
            $config->save('spid::service_provider.signing.privateKey', $signingPrivateKey);
            $config->save('spid::service_provider.signing.x509certificate', $signingX509certificate);
            $config->save('spid::service_provider.authenticationLevel', $authenticationLevel);
            $config->save('spid::service_provider.checkSignatures', $checkSignatures);
            $config->save('spid::service_provider.wantMessagesSigned', $wantMessagesSigned);
            $config->save('spid::service_provider.logMessages', $logMessages);
            $this->flash('success', t('The SPID configuration has been saved.'));

            return $this->app->make(ResponseFactoryInterface::class)->redirect($this->action(''), 302);
        }
    }

    /**
     * @param string $privateKey
     * @param string $x509certificate
     *
     * @return \Concrete\Core\Error\ErrorList\ErrorList
     */
    private function checkSigning($privateKey, $x509certificate)
    {
        $errors = $this->app->make('error');

        $key = @openssl_pkey_get_private($privateKey);
        if (!$key) {
            $errors->add(t('The private key is not valid.'));
        }
        $cert = @openssl_x509_read($x509certificate);
        if (!$cert) {
            $errors->add(t('The X.509 certificate key is not valid.'));
        } elseif (openssl_x509_checkpurpose($cert, X509_PURPOSE_SSL_SERVER) !== false) {
            $errors->add(t('The X.509 certificate is not valid for the server side of an SSL connection.'));
        }
        if ($key && $cert && !@openssl_x509_check_private_key($cert, $key)) {
            $errors->add(t('The private key does not correspond to the X.509 certificate.'));
        }

        if ($cert) {
            @openssl_x509_free($cert);
        }
        if ($key) {
            @openssl_free_key($key);
        }

        return $errors;
    }
}
