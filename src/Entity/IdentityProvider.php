<?php

namespace SPID\Entity;

use Concrete\Core\File\File;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(
 *     repositoryClass="SPID\Repository\IdentityProviderRepository",
 * )
 * @ORM\Table(
 *     name="SpidIdentityProviders",
 * )
 */
class IdentityProvider
{
    /**
     * URL kind: POST.
     *
     * @var string
     */
    const URLKIND_POST = 'post';

    /**
     * URL kind: redirect.
     *
     * @var string
     */
    const URLKIND_REDIRECT = 'redirect';

    /**
     * The identity provider record ID.
     *
     * @ORM\Column(type="integer", nullable=false, options={"unsigned": true, "comment": "Identity provider record ID"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    protected $ipRecordId;

    /**
     * The identity provider name.
     *
     * @ORM\Column(type="string", length=255, nullable=false, unique=true, options={"comment": "Identity provider name"})
     *
     * @var string
     */
    protected $ipName;

    /**
     * Is the identity provider enabled?
     *
     * @ORM\Column(type="boolean", nullable=false, options={"unsigned": true, "comment": "Is the identity provider enabled?"})
     *
     * @var bool
     */
    protected $ipEnabled;

    /**
     * The identity provider relative position.
     *
     * @ORM\Column(type="integer", nullable=false, options={"unsigned": true, "comment": "Identity provider relative position"})
     *
     * @var int
     */
    protected $ipSort;

    /**
     * The identity provider icon handle (or custom file ID).
     *
     * @ORM\Column(type="string", length=50, nullable=false, options={"unsigned": true, "comment": "Identity provider icon handle (or custom file ID)"})
     *
     * @var string
     */
    protected $ipIcon;

    /**
     * The identity provider metadata URL.
     *
     * @ORM\Column(type="text", nullable=false, options={"comment": "Identity provider metadata URL"})
     *
     * @var string
     */
    protected $ipMetadataUrl;

    /**
     * The identity provider entity ID.
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"comment": "Identity provider entity ID"})
     *
     * @var string
     */
    protected $ipEntityId;

    /**
     * Does the identity provider requires signed authorization requests?
     *
     * @ORM\Column(type="boolean", nullable=false, options={"unsigned": true, "comment": "Does the identity provider requires signed authorization requests?"})
     *
     * @var bool
     */
    protected $ipSignedAuthorizationRequests;

    /**
     * The identity provider login URLs.
     *
     * @ORM\Column(type="text", nullable=false, options={"comment": "Identity provider login URLs"})
     *
     * @var string
     */
    protected $ipLoginUrls;

    /**
     * The identity provider logout URL.
     *
     * @ORM\Column(type="text", nullable=false, options={"comment": "Identity provider logout URLs"})
     *
     * @var string
     */
    protected $ipLogoutUrls;

    /**
     * The identity provider X.509 certificate.
     *
     * @ORM\Column(type="text", nullable=false, options={"comment": "Identity provider X.509 certificate"})
     *
     * @var string
     */
    protected $ipX509Certificate;

    /**
     * The identity provider X.509 certificate expiration date/time (if available).
     *
     * @ORM\Column(type="datetime", nullable=true, options={"comment": "Identity provider X.509 certificate expiration date/time"})
     *
     * @var DateTime|null
     */
    protected $ipX509CertificateExpiration;

    /**
     * The attributes supported by the identity provider.
     *
     * @ORM\Column(type="text", nullable=false, options={"comment": "Attributes supported by the identity provider"})
     *
     * @var string
     */
    protected $ipSupportedAttributes;

    /**
     * Initialize the instance.
     */
    protected function __construct()
    {
    }

    /**
     * Create a new (empty & unsaved) entity.
     *
     * @return \SPID\Entity\IdentityProvider
     */
    public static function create()
    {
        $result = new static();

        return $result
            ->setIdentityProviderName('')
            ->setIdentityProviderEnabled(false)
            ->setIdentityProviderSort(0)
            ->setIdentityProviderIcon('')
            ->setIdentityProviderMetadataUrl('')
            ->setIdentityProviderEntityId('')
            ->setRequireSignedAuthorizationRequests(false)
            ->setIdentityProviderLoginUrls([])
            ->setIdentityProviderLogoutUrls([])
            ->setIdentityProviderX509Certificate('')
            ->setIdentityProviderX509CertificateExpiration(null)
            ->setIdentityProviderSupportedAttributes([])
        ;
    }

    /**
     * Get the identity provider record ID.
     *
     * @return int|null
     */
    public function getIdentityProviderRecordId()
    {
        return $this->ipRecordId;
    }

    /**
     * Set the identity provider name.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setIdentityProviderName($value)
    {
        $this->ipName = trim((string) $value);

        return $this;
    }

    /**
     * Get the identity provider name.
     *
     * @return string
     */
    public function getIdentityProviderName()
    {
        return $this->ipName;
    }

    /**
     * Get the identity provider display name.
     *
     * @return string
     */
    public function getIdentityProviderDisplayName()
    {
        return t($this->ipName);
    }

    /**
     * Is the identity provider enabled?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setIdentityProviderEnabled($value)
    {
        $this->ipEnabled = (bool) $value;

        return $this;
    }

    /**
     * Is the identity provider enabled?
     *
     * @return bool
     */
    public function isIdentityProviderEnabled()
    {
        return $this->ipEnabled;
    }

    /**
     * Set the identity provider relative position.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setIdentityProviderSort($value)
    {
        $this->ipSort = (int) $value;

        return $this;
    }

    /**
     * Get the identity provider relative position.
     *
     * @return int
     */
    public function getIdentityProviderSort()
    {
        return $this->ipSort;
    }

    /**
     * Set the identity provider icon handle (or file ID).
     *
     * @param string|int $value
     *
     * @return $this
     */
    public function setIdentityProviderIcon($value)
    {
        $this->ipIcon = (string) $value;

        return $this;
    }

    /**
     * Get the identity provider icon handle (or file ID).
     *
     * @return string|int
     */
    public function getIdentityProviderIcon()
    {
        return preg_match('/^[1-9][0-9]*$/', $this->ipIcon) ? (int) $this->ipIcon : $this->ipIcon;
    }

    /**
     * Get the identity provider icon HTML.
     *
     * @return string
     */
    public function getIdentityProviderIconHtml()
    {
        $src = '';
        $altSrc = '';
        $handle = $this->getIdentityProviderIcon();
        switch (gettype($handle)) {
            case 'integer':
                $file = File::getByID($handle);
                if ($file !== null) {
                    $fileVersion = $file->getApprovedVersion();
                    if ($fileVersion !== null) {
                        $url = $fileVersion->getURL();
                        if ($url) {
                            $src = $url;
                        }
                    }
                }
                break;
            case 'string':
                if ($handle !== '') {
                    $src = REL_DIR_PACKAGES . '/spid/images/idp/' . $handle . '.svg';
                    $altSrc = REL_DIR_PACKAGES . '/spid/images/idp/' . $handle . '.png';
                }
                break;
        }
        if ($src === '') {
            $src = REL_DIR_PACKAGES . '/spid/images/idp/-.svg';
            $altSrc = REL_DIR_PACKAGES . '/spid/images/idp/-.png';
        }
        $result = '<img class="spid-idp-logo" alt="' . h($this->getIdentityProviderDisplayName()) . '" src="' . h($src) . '"';
        if ($altSrc !== '') {
            $result .= ' onerror="' . h('this.onerror = null; this.src = ' . json_encode($altSrc) . ';') . '"';
        }
        $result .= ' />';

        return $result;
    }

    /**
     * Set the identity provider metadata URL.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setIdentityProviderMetadataUrl($value)
    {
        $this->ipMetadataUrl = trim((string) $value);

        return $this;
    }

    /**
     * Get the identity provider metadata URL.
     *
     * @return string
     */
    public function getIdentityProviderMetadataUrl()
    {
        return $this->ipMetadataUrl;
    }

    /**
     * Set the identity provider entity ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setIdentityProviderEntityId($value)
    {
        $this->ipEntityId = trim((string) $value);

        return $this;
    }

    /**
     * Get the identity provider entity ID.
     *
     * @return string
     */
    public function getIdentityProviderEntityId()
    {
        return $this->ipEntityId;
    }

    /**
     * Does the identity provider requires signed authorization requests?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setRequireSignedAuthorizationRequests($value)
    {
        $this->ipSignedAuthorizationRequests = (bool) $value;

        return $this;
    }

    /**
     * Does the identity provider requires signed authorization requests?
     *
     * @return bool
     */
    public function requireSignedAuthorizationRequests()
    {
        return $this->ipSignedAuthorizationRequests;
    }

    /**
     * Set the identity provider login URLs.
     *
     * @param array $value Keys: kind, values: URL
     *
     * @return $this
     */
    public function setIdentityProviderLoginUrls(array $value)
    {
        ksort($value);
        $this->ipLoginUrls = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);

        return $this;
    }

    /**
     * Set a specific identity provider login URL.
     *
     * @param string $kind One of the URLKIND_... constants
     * @param string $url The actual URL
     *
     * @return $this
     */
    public function setIdentityProviderLoginUrl($kind, $url)
    {
        $urls = $this->getIdentityProviderLoginUrls();
        $kind = trim((string) $kind);
        $url = trim((string) $url);
        if ($url === '') {
            unset($urls[$kind]);
        } else {
            $urls[$kind] = $url;
        }
        $this->setIdentityProviderLoginUrls($urls);

        return $this;
    }

    /**
     * Get the identity provider login URLs.
     *
     * @return array Keys: kind, values: URL
     */
    public function getIdentityProviderLoginUrls()
    {
        return json_decode($this->ipLoginUrls, true);
    }

    /**
     * Get a specific identity provider login URL.
     *
     * @param string $kind One of the URLKIND_... constants
     *
     * @return string Empty string if $kind is not available
     */
    public function getIdentityProviderLoginUrl($kind)
    {
        $kind = trim((string) $kind);
        $urls = $this->getIdentityProviderLoginUrls();

        return isset($urls[$kind]) ? $urls[$kind] : '';
    }

    /**
     * Set the identity provider logout URLs.
     *
     * @param array $value Keys: kind, values: URL
     *
     * @return $this
     */
    public function setIdentityProviderLogoutUrls(array $value)
    {
        ksort($value);
        $this->ipLogoutUrls = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);

        return $this;
    }

    /**
     * Set a specific identity provider logout URL.
     *
     * @param string $kind One of the URLKIND_... constants
     * @param string $url The actual URL
     *
     * @return $this
     */
    public function setIdentityProviderLogoutUrl($kind, $url)
    {
        $urls = $this->getIdentityProviderLogoutUrls();
        $kind = trim((string) $kind);
        $url = trim((string) $url);
        if ($url === '') {
            unset($urls[$kind]);
        } else {
            $urls[$kind] = $url;
        }
        $this->setIdentityProviderLogoutUrls($urls);

        return $this;
    }

    /**
     * Get the identity provider logout URLs.
     *
     * @return array Keys: kind, values: URL
     */
    public function getIdentityProviderLogoutUrls()
    {
        return json_decode($this->ipLogoutUrls, true);
    }

    /**
     * Get a specific identity provider logout URL.
     *
     * @param string $kind One of the URLKIND_... constants
     *
     * @return string Empty string if $kind is not available
     */
    public function getIdentityProviderLogoutUrl($kind)
    {
        $kind = trim((string) $kind);
        $urls = $this->getIdentityProviderLogoutUrls();

        return isset($urls[$kind]) ? $urls[$kind] : '';
    }

    /**
     * Set the identity provider X.509 certificate.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setIdentityProviderX509Certificate($value)
    {
        $this->ipX509Certificate = trim((string) $value);

        return $this;
    }

    /**
     * Get the identity provider X.509 certificate.
     *
     * @return string
     */
    public function getIdentityProviderX509Certificate()
    {
        return $this->ipX509Certificate;
    }

    /**
     * Set the identity provider X.509 certificate expiration date/time (if available).
     *
     * @param \DateTime|null $value
     *
     * @return $this
     */
    public function setIdentityProviderX509CertificateExpiration(DateTime $value = null)
    {
        $this->ipX509CertificateExpiration = $value;

        return $this;
    }

    /**
     * Get the identity provider X.509 certificate expiration date/time (if available).
     *
     * @return \DateTime|null
     */
    public function getIdentityProviderX509CertificateExpiration()
    {
        return $this->ipX509CertificateExpiration;
    }

    /**
     * Set the attributes supported by the identity provider.
     *
     * @param string[] $value
     *
     * @return $this
     */
    public function setIdentityProviderSupportedAttributes(array $value)
    {
        $value = array_unique(array_filter(array_map('trim', array_map('strval', $value))));
        sort($value);
        $this->ipSupportedAttributes = json_encode(array_values($value), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $this;
    }

    /**
     * Get the attributes supported by the identity provider.
     *
     * @return string[]
     */
    public function getIdentityProviderSupportedAttributes()
    {
        return json_decode($this->ipSupportedAttributes, true);
    }
}
