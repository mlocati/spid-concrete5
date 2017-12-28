<?php

namespace SPID;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\User\Group\Group;
use Concrete\Core\User\RegistrationService;
use Concrete\Core\User\User as CoreUser;
use Concrete\Core\User\UserInfoRepository;
use Illuminate\Support\Str;
use SPID\Attributes\LocalAttributes\LocalAttributeDynamic;
use SPID\Attributes\LocalAttributes\LocalAttributeFactory;
use SPID\Attributes\SpidAttributes;
use UserAttributeKey;

/**
 * User-related service.
 */
class User
{
    /**
     * The SPID configuration.
     *
     * @var \Concrete\Core\Config\Repository\Liaison
     */
    protected $config;

    /**
     * The SPID attributes services.
     *
     * @var \SPID\Attributes\SpidAttributes
     */
    protected $spidAttributes;

    /**
     * The core configuration.
     *
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $appConfig;

    /**
     * The database connection.
     *
     * @var \Concrete\Core\Database\Connection\Connection
     */
    protected $connection;

    /**
     * The UserInfo repository.
     *
     * @var \Concrete\Core\User\UserInfoRepository
     */
    protected $userInfoRepository;

    /**
     * @var \Concrete\Core\User\RegistrationService
     */
    protected $registrationService;

    /**
     * @var \SPID\Attributes\LocalAttributes\LocalAttributeFactory
     */
    protected $localAttributeFactory;

    /**
     * Initialize the service.
     *
     * @param \Concrete\Core\Config\Repository\Liaison $config
     * @param \SPID\Attributes\SpidAttributes $spidAttributes
     * @param Repository $appConfig
     * @param Connection $connection
     * @param UserInfoRepository $userInfoRepository
     * @param RegistrationService $registrationService
     * @param LocalAttributeFactory $localAttributeFactory
     */
    public function __construct($config, SpidAttributes $spidAttributes, Repository $appConfig, Connection $connection, UserInfoRepository $userInfoRepository, RegistrationService $registrationService, LocalAttributeFactory $localAttributeFactory)
    {
        $this->config = $config;
        $this->spidAttributes = $spidAttributes;
        $this->appConfig = $appConfig;
        $this->connection = $connection;
        $this->userInfoRepository = $userInfoRepository;
        $this->registrationService = $registrationService;
        $this->localAttributeFactory = $localAttributeFactory;
    }

    /**
     * @param array $attributes
     *
     * @return \Concrete\Core\User\User|null
     */
    public function loginByAttributes(array $attributes)
    {
        $user = null;
        if ($this->appConfig->get('concrete.user.registration.email_registration')) {
            $loginField = 'uEmail';
            $loginFieldValue = $this->getAttributeValueByMapped('f:uEmail', $attributes);
        } else {
            $loginField = 'uName';
            $loginFieldValue = $this->getAttributeValueByMapped('f:uName', $attributes);
        }
        if ($loginFieldValue !== null) {
            $row = $this->connection->fetchAssoc("select uID, uIsActive from Users where {$loginField} = ? limit 1", [$loginFieldValue]);
            if ($row) {
                if ($row['uIsActive']) {
                    $user = CoreUser::getByUserID($row['uID'], true);
                }
            } else {
                if ($this->config->get('registration.enabled')) {
                    $user = $this->createByAttributes($attributes);
                }
            }
        }

        return $user;
    }

    /**
     * @param array $attributes
     *
     * @return \Concrete\Core\User\User|null
     */
    public function createByAttributes(array $attributes)
    {
        $user = null;
        $email = $this->getAttributeValueByMapped('f:uEmail', $attributes);
        if ($email !== null) {
            if ($this->userInfoRepository->getByEmail($email) === null) {
                $username = $this->getAttributeValueByMapped('f:uName', $attributes);
                if ($username === null) {
                    $firstName = isset($attributes[SpidAttributes::ID_NAME]) ? $attributes[SpidAttributes::ID_NAME] : null;
                    $lastName = isset($attributes[SpidAttributes::ID_FAMILYNAME]) ? $attributes[SpidAttributes::ID_FAMILYNAME] : null;
                    if ($firstName !== null || $lastName !== null) {
                        $username = preg_replace('/[^a-z0-9\_]/', '_', strtolower($firstName . ' ' . $lastName));
                        $username = trim(preg_replace('/_{2,}/', '_', $username), '_');
                    } else {
                        $username = preg_replace('/[^a-zA-Z0-9\_]/i', '_', strtolower(substr($email, 0, strpos($email, '@'))));
                        $username = trim(preg_replace('/_{2,}/', '_', $username), '_');
                    }
                    $freeUsername = $username;
                    $suffix = 1;
                    while ($this->userInfoRepository->getByName($freeUsername) !== null) {
                        $freeUsername = $username . '_' . $suffix;
                        ++$suffix;
                    }
                    $username = $freeUsername;
                    $data = [
                        'uName' => $username,
                        'uPassword' => Str::random(256),
                        'uEmail' => $email,
                        'uIsValidated' => 1,
                    ];
                    $userInfo = $this->registrationService->create($data);
                    if ($userInfo) {
                        $defaultAttributeValues = UserAttributeKey::getRegistrationList();
                        if (!empty($defaultAttributeValues)) {
                            $userInfo->saveUserAttributesDefault($defaultAttributeValues);
                        }
                        $mapping = $this->config->get('mapped_attributes');
                        foreach ($attributes as $spidHandle => $attributeValue) {
                            if (isset($mapping[$spidHandle])) {
                                $localAttribute = $this->localAttributeFactory->getLocalAttribyteByHandle($mapping[$spidHandle]);
                                if ($localAttribute instanceof LocalAttributeDynamic) {
                                    $userInfo->setAttribute($localAttribute->getAttributeKey(), $mapping[$spidHandle]);
                                }
                            }
                        }
                        $user = CoreUser::loginByUserID($userInfo->getUserID());
                        $groupId = $this->config->get('registration.groupId');
                        if ($groupId) {
                            $groupId = (int) $groupId;
                            if ($groupId > 0) {
                                $group = Group::getByID($groupId);
                                if ($group && !$group->isError()) {
                                    $user->enterGroup($group);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $user;
    }

    /**
     * @param string $mappedHandle
     * @param array $attributes
     *
     * @return mixed|null
     */
    private function getAttributeValueByMapped($mappedHandle, array $attributes)
    {
        $mapping = $this->config->get('mapped_attributes');
        $spidHandle = array_search($mappedHandle, $mapping, true);
        if ($spidHandle !== false && isset($attributes[$spidHandle])) {
            $result = $attributes[$spidHandle];
        } else {
            $result = null;
        }

        return $result;
    }
}
