<?php

namespace Oro\Bundle\ConfigBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\UserScopeManager;
use Oro\Bundle\UserBundle\Entity\User;

class GlobalConfigManager
{
    /** ConfigManager */
    protected $configManager;

    /** @var UserScopeManager */
    protected $userScopeManager;

    /**
     * @param ConfigManager    $configManager
     * @param UserScopeManager $userScopeManager
     */
    public function __construct(
        ConfigManager $configManager = null,
        UserScopeManager $userScopeManager = null
    ) {
        $this->configManager = $configManager;
        $this->userScopeManager = $userScopeManager;
    }

    /**
     * @param User   $user
     * @param string $signature
     * @return bool
     */
    public function saveUserConfigSignature(User $user, $signature)
    {
        $this->setContext($user);
        $newSettings = [implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_email', 'signature']) => $signature];
            $this->configManager->save($newSettings);
        $this->restoreContext();
    }

    /**
     * @return string|null
     */
    public function getUserConfigSignature()
    {
        return $this->userScopeManager->getSettingValue('oro_email.signature');
    }

    /**
     * Sets context of user
     *
     * @param User $user
     * @return bool
     */
    protected function setContext(User $user)
    {
        if (in_array(null, [$this->configManager, $this->userScopeManager], true)) {
            throw new \RuntimeException('Unable to save user config, unmet dependencies detected.');
        }

        $this->configManager->addManager($this->userScopeManager->getScopedEntityName(), $this->userScopeManager);
        $this->configManager->setScopeName($this->userScopeManager->getScopedEntityName());
        $this->configManager->setScopeId($user->getId());

        return true;
    }

    /**
     * Restores current user context
     */
    protected function restoreContext()
    {
        $this->configManager->setScopeId();
    }
}
