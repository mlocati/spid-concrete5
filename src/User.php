<?php

namespace SPID;

use Concrete\Core\Application\Application;
use Concrete\Core\Attribute\Category\UserCategory;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\User\Group\Group;
use Concrete\Core\User\RegistrationService;
use Concrete\Core\User\User as CoreUser;
use Concrete\Core\User\UserInfoRepository;
use Exception;
use Illuminate\Support\Str;
use SPID\Attributes\LocalAttributes\LocalAttributeDynamic;
use SPID\Attributes\LocalAttributes\LocalAttributeFactory;
use SPID\Attributes\SpidAttributes;
use SPID\Entity\IdentityProvider;
use Throwable;

/**
 * User-related service.
 */
class User
{
    /**
     * The application container instance.
     *
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * The SPID configuration.
     *
     * @var \Concrete\Core\Config\Repository\Liaison
     */
    protected $spidConfig;

    /**
     * Initialize the service.
     *
     * @param \Concrete\Core\Application\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->spidConfig = $this->app->make('spid/config');
    }

    /**
     * Perform the user login using the attributes provided by a SPID identity provider.
     *
     * @param \SPID\Entity\IdentityProvider $identityProvider
     * @param array $attributes
     *
     * @return \Concrete\Core\Error\ErrorList\ErrorList
     */
    public function loginByAttributes(IdentityProvider $identityProvider, array $attributes)
    {
        $result = $this->app->make('error');
        if (isset($attributes[SpidAttributes::ID_SPIDCODE])) {
            $spidCode = trim((string) $attributes[SpidAttributes::ID_SPIDCODE]);
        } else {
            $spidCode = '';
        }
        if ($spidCode === '') {
            $result->add(t('SPID Code not received.'));
        } else {
            $db = $this->app->make(Connection::class);
            $row = $db->fetchAssoc('SELECT Users.uID, Users.uIsActive from SpidUsers INNER JOIN Users ON SpidUsers.uID = Users.uID WHERE SpidUsers.identityProvider = ? AND SpidUsers.spidCode = ? LIMIT 1', [$identityProvider->getIdentityProviderEntityId(), $spidCode]);
            if ($row !== false) {
                if (empty($row['uIsActive'])) {
                    $result->add(t('The user is inactive.'));
                } else {
                    CoreUser::getByUserID($rpw['uID'], true);
                }
            } elseif ($this->spidConfig->get('registration.enabled')) {
                $result->add($this->createByAttributes($identityProvider, $spidCode, $attributes));
            } else {
                $result->add(t('Unknown user.'));
            }
        }

        return $result;
    }

    /**
     * Create a new user and log in using the attributes provided by a SPID identity provider.
     *
     * @param \SPID\Entity\IdentityProvider $identityProvider
     * @param string $spidCode
     * @param array $attributes
     *
     * @return \Concrete\Core\Error\ErrorList\ErrorList
     */
    private function createByAttributes(IdentityProvider $identityProvider, $spidCode, array $attributes)
    {
        $result = $this->app->make('error');
        $email = $this->getAttributeValueByMapped('f:uEmail', $attributes);
        if (!$email) {
            $result->add(t('Unable to create the user: email address not available.'));
        } else {
            $userInfoRepository = $this->app->make(UserInfoRepository::class);
            if ($userInfoRepository->getByEmail($email) !== null) {
                $result->add(t('Another user with the email address %s already exists.', $email));
            } else {
                $registrationService = $this->app->make(RegistrationService::class);
                $username = $registrationService->getNewUsernameFromUserDetails(
                    $email,
                    $this->getAttributeValueByMapped('f:uName', $attributes),
                    isset($attributes[SpidAttributes::ID_NAME]) ? $attributes[SpidAttributes::ID_NAME] : '',
                    isset($attributes[SpidAttributes::ID_FAMILYNAME]) ? $attributes[SpidAttributes::ID_FAMILYNAME] : ''
                );
                $data = [
                    'uName' => $username,
                    'uPassword' => Str::random(256),
                    'uEmail' => $email,
                    'uIsValidated' => 1,
                ];
                $connection = $this->app->make(Connection::class);
                $connection->beginTransaction();
                $exception = null;
                try {
                    $userInfo = $registrationService->create($data);
                    if (!$userInfo) {
                        $result->add(t('User registration aborted.'));
                    } else {
                        $userAttributeCategory = $this->app->make(UserCategory::class);
                        $defaultAttributeValues = $userAttributeCategory->getRegistrationList();
                        if (!empty($defaultAttributeValues)) {
                            $userInfo->saveUserAttributesDefault($defaultAttributeValues);
                        }
                        $localAttributeFactory = $this->app->make(LocalAttributeFactory::class);
                        $mapping = $this->spidConfig->get('mapped_attributes');
                        foreach ($attributes as $spidHandle => $attributeValue) {
                            if (isset($mapping[$spidHandle])) {
                                $localAttribute = $localAttributeFactory->getLocalAttribyteByHandle($mapping[$spidHandle]);
                                if ($localAttribute instanceof LocalAttributeDynamic) {
                                    $userInfo->setAttribute($localAttribute->getAttributeKey(), $attributeValue);
                                }
                            }
                        }
                        $user = CoreUser::getByUserID($userInfo->getUserID(), true);
                        $groupId = (int) $this->spidConfig->get('registration.groupId');
                        if ($groupId > 0) {
                            $group = Group::getByID($groupId);
                            if ($group && !$group->isError()) {
                                $user->enterGroup($group);
                            }
                        }
                        $connection->executeQuery(
                            'INSERT INTO SpidUsers (identityProvider, spidCode, uID) VALUES (?, ?, ?)',
                            [$identityProvider->getIdentityProviderEntityId(), $spidCode, $userInfo->getUserID()]
                        );
                    }
                    $connection->commit();
                } catch (Exception $x) {
                    $exception = $x;
                } catch (Throwable $x) {
                    $exception = $x;
                }
                if ($exception !== null) {
                    try {
                        $connection->rollBack();
                    } catch (Exception $foo) {
                    } catch (Throwable $foo) {
                    }
                    if ($exception instanceof UserMessageException) {
                        $result->add($exception);
                    } else {
                        $result->add(t('An unspecified error occurred.'));
                    }
                }
            }
        }

        return $result;
    }
}
