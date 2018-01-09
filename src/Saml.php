<?php

namespace SPID;

use Concrete\Core\Config\Repository\Liaison;
use Concrete\Core\Http\Request;
use Concrete\Core\Url\Resolver\Manager\ResolverManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OneLogin_Saml2_Auth;
use OneLogin_Saml2_Error;
use OneLogin_Saml2_Settings;
use RuntimeException;
use SPID\Attributes\SpidAttributes;
use SPID\Entity\IdentityProvider;
use Throwable;
use XMLSecurityDSig;
use XMLSecurityKey;

class Saml
{
    /**
     * @var Liaison
     */
    protected $config;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ResolverManager
     */
    protected $urlResolver;

    /**
     * @var SpidAttributes
     */
    protected $spidAttributes;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Liaison $config
     * @param Request $request
     * @param ResolverManager $urlResolver
     * @param SpidAttributes $spidAttributes
     * @param AuthenticationLevel $authenticationLevel
     * @param EntityManagerInterface $entityManager
     * @param Logger $logger
     */
    public function __construct($config, Request $request, ResolverManager $urlResolver, SpidAttributes $spidAttributes, EntityManagerInterface $entityManager, Logger $logger)
    {
        $this->config = $config;
        $this->request = $request;
        $this->urlResolver = $urlResolver;
        $this->spidAttributes = $spidAttributes;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @param IdentityProvider $identityProvider
     *
     * @return string|null
     */
    public function getLoginUrl(IdentityProvider $identityProvider)
    {
        $result = null;
        try {
            $relayState = $this->createRelayState($identityProvider);
            $configuration = $this->getConfiguration($identityProvider);
            $auth = new OneLogin_Saml2_Auth($configuration);
            $result = $auth->login(/*$returnTo*/ $relayState, /*$parameters*/[], /*$forceAuthn*/false, /*$isPassive*/false, /*$stay*/true);
            $this->logger->logOutboundMessage($auth->getLastRequestXML());
        } catch (Exception $x) {
            $this->logger->addError($x);
        } catch (Throwable $x) {
            $this->logger->addError($x);
        }

        return $result;
    }

    /**
     * @param IdentityProvider $identityProvider
     *
     * @return array|null
     */
    public function handleAssertion(IdentityProvider $identityProvider)
    {
        $result = null;
        try {
            $configuration = $this->getConfiguration($identityProvider);
            $auth = new OneLogin_Saml2_Auth($configuration);
            $auth->processResponse();
            $this->logger->logInboundMessage($auth->getLastResponseXML());
            $error = $auth->getLastErrorReason();
            if ($error !== null) {
                $this->logger->addError($error);
            } elseif (!$auth->isAuthenticated()) {
                $this->logger->addError(t('User is not authenticated.'));
            } else {
                $attributes = $auth->getAttributes();
                if (!empty($attributes)) {
                    foreach ($attributes as $attributeHandle => $attributeValues) {
                        $attributeValue = is_array($attributeValues) ? array_shift($attributeValues) : $attributeValues;
                        $normalizedValue = $this->spidAttributes->normalizeAttributeValue($attributeHandle, $attributeValue);
                        if ($normalizedValue !== null) {
                            if ($result === null) {
                                $result = [];
                            }
                            $result[$attributeHandle] = $normalizedValue;
                        }
                    }
                }
            }
        } catch (Exception $x) {
            $this->logger->addError($x);
        } catch (Throwable $x) {
            $this->logger->addError($x);
        }

        return $result;
    }

    /**
     * @return array|null
     */
    public function parseRelayState()
    {
        $result = null;
        $serialized = $this->request->request->get('RelayState');
        if (is_string($serialized) && $serialized !== '') {
            $unserialized = @base64_decode($serialized);
            if ($unserialized !== false) {
                $chunks = explode('+', $unserialized, 2);
                if (count($chunks) === 2) {
                    $identityProvider = $this->entityManager->find(IdentityProvider::class, $chunks[0]);
                    if ($identityProvider !== null && $identityProvider->isIdentityProviderEnabled()) {
                        $result = [$identityProvider, $chunks[1]];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @throws OneLogin_Saml2_Error
     *
     * @return string
     */
    public function getMetadata()
    {
        $configuration = $this->getBaseConfiguration();
        $settings = new OneLogin_Saml2_Settings($configuration, true);
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (!empty($errors)) {
            throw new OneLogin_Saml2_Error(t('Invalid service provider metadata: %s', implode(', ', $errors)), OneLogin_Saml2_Error::METADATA_SP_INVALID);
        }

        return $metadata;
    }

    /**
     * @param string $data
     * @param IdentityProvider $identityProvider
     *
     * @return string
     */
    protected function createRelayState(IdentityProvider $identityProvider)
    {
        return base64_encode(implode('+', [
            (string) $identityProvider->getIdentityProviderRecordId(),
            (string) $this->getUrlFromDestination(),
        ]));
    }

    /**
     * Constructs a full URL from the 'destination' parameter.
     *
     * @throws \RuntimeException
     *   If the destination is disallowed
     *
     * @return string|null
     *   The full absolute URL (i.e. leading back to ourselves), or NULL if no
     *   destination parameter was given. This value is tuned to what login() /
     *   logout() expect for an input argument.
     */
    protected function getUrlFromDestination()
    {
        $destination = $this->request->query->get('destination');
        $destination = is_string($destination) ? trim($destination) : '';
        if ($destination !== '') {
            if ($this->isExternal($destination)) {
                throw new RuntimeException("Destination URL query parameter must not be external: $destination");
            }
            if (strpos($destination, '/') !== 0) {
                $destination = '/' . $destination;
            }
            $result = (string) $this->urlResolver->resolve([$destination]);
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    protected function isExternal($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        $result = false;

        return (bool) preg_match('/^\w*:?\/\/', $path);
    }

    /**
     * Get the Onelogin/php-saml configuration array.
     *
     * @return array
     */
    protected function getBaseConfiguration()
    {
        $result = [
            'sp' => [
                'entityId' => $this->config->get('service_provider.entityId'),
                'assertionConsumerService' => [
                    'url' => (string) $this->urlResolver->resolve(['/spid/assertionConsumerService']),
                ],
                'attributeConsumingService' => [
                    'serviceName' => 'AttributeConsumingServiceName',
                    'requestedAttributes' => [],
                ],
                'singleLogoutService' => [
                    'url' => (string) $this->urlResolver->resolve(['/spid/singleLogoutService']),
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
                'privateKey' => $this->config->get('service_provider.signing.privateKey'),
                'x509cert' => $this->config->get('service_provider.signing.x509certificate'),
            ],
            'security' => [
                'authnRequestsSigned' => true,
                'wantMessagesSigned' => (bool) $this->config->get('service_provider.wantMessagesSigned'),
                'signMetadata' => true,
                'wantAssertionsSigned' => true,
                'signatureAlgorithm' => XMLSecurityKey::RSA_SHA256,
                'digestAlgorithm' => XMLSecurityDSig::SHA256,
                'requestedAuthnContext' => [
                    $this->config->get('service_provider.authenticationLevel') ?: AuthenticationLevel::DEFAULT_LEVEL,
                ],
                'requestedAuthnContextComparison' => 'minimum',
            ],
            'strict' => (bool) $this->config->get('service_provider.checkSignatures'),
        ];
        $mappedAttributes = $this->config->get('mapped_attributes');
        $attributeKeys = array_keys($mappedAttributes);
        if (in_array(SpidAttributes::ID_SPIDCODE, $attributeKeys, true) === false) {
            $attributeKeys[] = SpidAttributes::ID_SPIDCODE;
        }
        foreach ($mappedAttributes as $attributeKey) {
            $result['sp']['attributeConsumingService']['requestedAttributes'][] = [
                'name' => $attributeKey,
                'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                'friendlyName' => $this->spidAttributes->getAttributeName($attributeKey),
            ];
        }

        return $result;
    }

    /**
     * Get the Onelogin/php-saml configuration array.
     *
     * @param IdentityProvider $identityProvider
     *
     * @return array
     */
    protected function getConfiguration(IdentityProvider $identityProvider)
    {
        $result = $this->getBaseConfiguration();
        $result['idp'] = [
            'entityId' => $identityProvider->getIdentityProviderEntityId(),
            'singleSignOnService' => [
                'url' => $identityProvider->getIdentityProviderLoginUrl('redirect'),
            ],
            'singleLogoutService' => [
                'url' => $identityProvider->getIdentityProviderLogoutUrl('redirect'),
            ],
            'x509cert' => $identityProvider->getIdentityProviderX509Certificate(),
        ];
        $result['security']['authnRequestsSigned'] = $identityProvider->requireSignedAuthorizationRequests();

        return $result;
    }
}
