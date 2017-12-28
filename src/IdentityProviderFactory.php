<?php

namespace SPID;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\Client\Client as HttpClient;
use Concrete\Core\Localization\Service\Date;
use DateTime;
use Exception;
use SimpleXMLElement;
use SPID\Entity\IdentityProvider;
use Zend\Http\Request;

class IdentityProviderFactory
{
    /**
     * @var string
     */
    const NAMESPACE_SAML20METADATA = 'urn:oasis:names:tc:SAML:2.0:metadata';

    /**
     * @var string
     */
    const NAMESPACE_SAML20ASSERTION = 'urn:oasis:names:tc:SAML:2.0:assertion';

    /**
     * @var string
     */
    const NAMESPACE_XMLSIGNATURE = 'http://www.w3.org/2000/09/xmldsig#';

    /**
     * @var \Concrete\Core\Http\Client\Client
     */
    protected $httpClient;

    /**
     * @var \Concrete\Core\Localization\Service\Date
     */
    protected $dateService;

    /**
     * @var \SPID\PEM
     */
    protected $pem;

    /**
     * Initialize the instance.
     *
     * @param \Concrete\Core\Http\Client\Client $httpClient
     * @param \Concrete\Core\Localization\Service\Date $dateService
     * @param \SPID\PEM $pem
     */
    public function __construct(HttpClient $httpClient, Date $dateService, PEM $pem)
    {
        $this->httpClient = $httpClient;
        $this->dateService = $dateService;
        $this->pem = $pem;
    }

    /**
     * Get the metadata URLs of the default identity providers.
     *
     * @return array keys are the identity provider names, values are [metadata URL, icon handle]
     *
     * @see http://www.agid.gov.it/infrastrutture-architetture/spid/identity-provider-accreditati
     */
    public function getDefaultIdentityProviderMetadataUrls()
    {
        return [
            'Aruba ID' => ['https://loginspid.aruba.it/metadata', 'aruba-id'],
            'Infocert ID' => ['https://identity.infocert.it/metadata/metadata.xml', 'infocert-id'],
            'Intesa ID' => ['https://spid.intesa.it/metadata/metadata.xml', 'intesa-id'],
            'Namirial ID' => ['https://idp.namirialtsp.com/idp/metadata', 'namirial-id'],
            'Poste ID' => ['http://posteid.poste.it/jod-fs/metadata/metadata.xml', 'poste-id'],
            'Sielte ID' => ['https://identity.sieltecloud.it/simplesaml/metadata.xml', 'sielte-id'],
            'SPID Italia' => ['https://spid.register.it/login/metadata', 'spid-italia'],
            'Tim ID' => ['https://login.id.tim.it/spid-services/MetadataBrowser/idp', 'tim-id'],
        ];
    }

    /**
     * Update the identity provider from its metadata URL.
     *
     * @param IdentityProvider $identityProvider
     */
    public function refreshIdentityProviderFromMetadataUrl(IdentityProvider $identityProvider)
    {
        $url = $identityProvider->getIdentityProviderMetadataUrl();
        if (!$url) {
            throw new UserMessageException(t('The identity provider does not specify a metadata URL.'));
        }

        return $this->getIdentityProviderFromMetadataUrl($url, $identityProvider);
    }

    /**
     * Create a new identity provider from a metadata URL (or update an existing identity provider).
     *
     * @param string $url the URL of the metadata
     * @param IdentityProvider|null $identityProvider The identity provider to be updated (if NULL: a new identity provider instance will be created)
     *
     * @throws Exception
     *
     * @return \SPID\Entity\IdentityProvider
     */
    public function getIdentityProviderFromMetadataUrl($url, IdentityProvider $identityProvider = null)
    {
        $request = new Request();
        $request->setUri($url);
        $this->httpClient->reset();
        $response = $this->httpClient->send($request);
        if (!$response->isSuccess()) {
            throw new UserMessageException(t('Failed to get the Identity Provider metadata from %1$s: %2$s', $url, $response->getReasonPhrase()));
        }
        $body = $response->getBody();

        return $this->getIdentityProviderFromMetadataString($body, $identityProvider);
    }

    /**
     * Create a new identity provider from a metadata XML (or update an existing identity provider).
     *
     * @param string $xml the metadata XML
     * @param IdentityProvider|null $identityProvider The identity provider to be updated (if NULL: a new identity provider instance will be created)
     *
     * @throws Exception
     *
     * @return \SPID\Entity\IdentityProvider
     */
    public function getIdentityProviderFromMetadataString($xml, IdentityProvider $identityProvider = null)
    {
        libxml_clear_errors();
        $prevXmlInternalErrors = libxml_use_internal_errors(true);
        try {
            $xDoc = simplexml_load_string($xml, null, LIBXML_NOCDATA);
            if ($xDoc === false) {
                $errorLines = [];
                $xmlErrors = libxml_get_errors();
                if (!empty($xmlErrors)) {
                    foreach ($xmlErrors as $xmlError) {
                        $line = trim((string) $xmlError->message);
                        if ($line !== '') {
                            $errorLines[] = $line;
                        }
                    }
                }
                $message = implode("\n", $errorLines);
                if ($message === '') {
                    $message = t('Unknown error while parsing the Identity Provider metadata');
                } else {
                    $message = t('The following error occurred whiole parsing the Identity Provider metadata:' . "\n" . $message);
                }
                throw new UserMessageException($message);
            }
        } finally {
            libxml_use_internal_errors($prevXmlInternalErrors);
        }

        return $this->getIdentityProviderFromMetadataXml($xDoc, $identityProvider);
    }

    /**
     * @param SimpleXMLElement $xml
     * @param IdentityProvider|null $identityProvider
     *
     * @throws Exception
     *
     * @return \SPID\Entity\IdentityProvider
     */
    protected function getIdentityProviderFromMetadataXml(SimpleXMLElement $xml, IdentityProvider $identityProvider = null)
    {
        if ($identityProvider === null) {
            $identityProvider = IdentityProvider::create();
        }
        $xml->registerXPathNamespace('md', static::NAMESPACE_SAML20METADATA);
        $xml->registerXPathNamespace('a', static::NAMESPACE_SAML20ASSERTION);
        $xml->registerXPathNamespace('ds', static::NAMESPACE_XMLSIGNATURE);
        $nodes = $xml->xpath('/md:EntityDescriptor');
        if (!is_array($nodes) || count($nodes) !== 1) {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%s node is missing', 'EntityDescriptor')));
        }
        $entityDescriptor = $nodes[0];
        $identityProvider->setIdentityProviderEntityId(isset($entityDescriptor['entityID']) ? $entityDescriptor['entityID'] : '');
        if ($identityProvider->getIdentityProviderEntityId() === '') {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%s attribute is missing', 'entityID')));
        }
        $nodes = $xml->xpath('/md:EntityDescriptor/md:IDPSSODescriptor');
        if (!is_array($nodes) || count($nodes) !== 1) {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%s node is missing', 'IDPSSODescriptor')));
        }
        $idpSSODescriptor = $nodes[0];
        $protocolSupportEnumeration = isset($idpSSODescriptor['protocolSupportEnumeration']) ? trim((string) $idpSSODescriptor['protocolSupportEnumeration']) : '';
        if ($protocolSupportEnumeration === '') {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%s attribute is missing', 'protocolSupportEnumeration')));
        }
        $protocolSupports = preg_split('/\s+/', $protocolSupportEnumeration, -1, PREG_SPLIT_NO_EMPTY);
        if (!in_array('urn:oasis:names:tc:SAML:2.0:protocol', $protocolSupports, true)) {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%s protocol is not supported', 'urn:oasis:names:tc:SAML:2.0:protocol')));
        }
        $wantAuthnRequestsSigned = isset($idpSSODescriptor['WantAuthnRequestsSigned']) ? trim((string) $idpSSODescriptor['WantAuthnRequestsSigned']) : '';
        if ($wantAuthnRequestsSigned === '') {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%s attribute is missing', 'WantAuthnRequestsSigned')));
        }
        switch (strtolower($wantAuthnRequestsSigned)) {
            case 'true':
            case '1':
            case 'on':
                $identityProvider->setRequireSignedAuthorizationRequests(true);
                break;
            case 'false':
            case '0':
            case 'off':
                $identityProvider->setRequireSignedAuthorizationRequests(false);
                break;
            case '':
                throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%1$s attribute has an invalid value (%2$s)', 'wantAuthnRequestSigned', $wantAuthnRequestSignedString)));
        }
        $nodes = $xml->xpath('/md:EntityDescriptor/md:IDPSSODescriptor/md:KeyDescriptor[@use="signing"]/ds:KeyInfo/ds:X509Data/ds:X509Certificate');
        if (!is_array($nodes) || count($nodes) !== 1) {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%s node is missing', 'X509Certificate')));
        }
        $identityProvider->setIdentityProviderX509Certificate($this->pem->simplify((string) $nodes[0]));
        $check = @openssl_x509_parse($this->pem->format($identityProvider->getIdentityProviderX509Certificate(), PEM::KIND_X509CERTIFICATE));
        if (empty($check)) {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('invalid X.509 certificate', 'KeyDescriptor')));
        }
        $identityProvider->setIdentityProviderX509CertificateExpiration(null);
        if (is_array($check)) {
            if (isset($check['validTo_time_t']) && is_int($check['validTo_time_t'])) {
                $identityProvider->setIdentityProviderX509CertificateExpiration(new DateTime('@' . $check['validTo_time_t']));
                if ($identityProvider->getIdentityProviderX509CertificateExpiration() < new DateTime('now')) {
                    throw new UserMessageException(t(
                        'The Identity Provider metadata is not valid: %s',
                        t('the X.509 certificate expired on %s', $this->dateService->formatDateTime($identityProvider->getIdentityProviderX509CertificateExpiration(), true, false))
                    ));
                }
            }
        }
        $nodes = $xml->xpath('/md:EntityDescriptor/md:IDPSSODescriptor/md:NameIDFormat');
        if (!is_array($nodes) || count($nodes) !== 1) {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('%s node is missing', 'NameIDFormat')));
        }
        $nameIDFormat = trim((string) $nodes[0]);
        if ($nameIDFormat !== 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient') {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('invalid %1$s node value: %2$s', 'NameIDFormat', 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient')));
        }
        $identityProvider->setIdentityProviderLoginUrls([]);
        $nodes = $xml->xpath('/md:EntityDescriptor/md:IDPSSODescriptor/md:SingleSignOnService');
        if (is_array($nodes)) {
            foreach ($nodes as $node) {
                $binding = isset($node['Binding']) ? trim((string) $node['Binding']) : '';
                switch ($binding) {
                    case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST':
                        $location = isset($node['Location']) ? trim((string) $node['Location']) : '';
                        if ($location === '') {
                            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('empty %s attribute in', 'Location')));
                        }
                        $identityProvider->setIdentityProviderLoginUrl(IdentityProvider::URLKIND_POST, $location);
                        break;
                    case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect':
                        $location = isset($node['Location']) ? trim((string) $node['Location']) : '';
                        if ($location === '') {
                            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('empty %s attribute in', 'Location')));
                        }
                        $identityProvider->setIdentityProviderLoginUrl(IdentityProvider::URLKIND_REDIRECT, $location);
                        break;
                }
            }
        }
        if (empty($identityProvider->getIdentityProviderLoginUrls())) {
            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('no login locations recognized')));
        }
        $identityProvider->setIdentityProviderLogoutUrls([]);
        $nodes = $xml->xpath('/md:EntityDescriptor/md:IDPSSODescriptor/md:SingleLogoutService');
        if (is_array($nodes)) {
            foreach ($nodes as $node) {
                $binding = isset($node['Binding']) ? trim((string) $node['Binding']) : '';
                switch ($binding) {
                    case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST':
                        $location = isset($node['Location']) ? trim((string) $node['Location']) : '';
                        if ($location === '') {
                            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('empty %s attribute in', 'Location')));
                        }
                        $identityProvider->setIdentityProviderLogoutUrl(IdentityProvider::URLKIND_POST, $location);
                        break;
                    case 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect':
                        $location = isset($node['Location']) ? trim((string) $node['Location']) : '';
                        if ($location === '') {
                            throw new UserMessageException(t('The Identity Provider metadata is not valid: %s', t('empty %s attribute in', 'Location')));
                        }
                        $identityProvider->setIdentityProviderLogoutUrl(IdentityProvider::URLKIND_REDIRECT, $location);
                        break;
                }
            }
        }
        $attributes = [];
        $nodes = $xml->xpath('/md:EntityDescriptor/md:IDPSSODescriptor/a:Attribute');
        if (!empty($nodes)) {
            foreach ($nodes as $node) {
                $name = isset($node['Name']) ? trim((string) $node['Name']) : '';
                if ($name !== '') {
                    $attributes[] = $name;
                }
            }
        }
        $identityProvider->setIdentityProviderSupportedAttributes($attributes);

        return $identityProvider;
    }
}
