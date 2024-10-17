<?php
/**
 * @author    :author_name <author@domain.com>
 * @copyright Since :author_copyright_year :author_company
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

namespace PrestaShop\Module\Skeleton\Install;

use IBroStudio\ModuleHelper\Install\InstallManager;
use Module;
use PrestaShop\Module\Skeleton\Enums\SkeletonConfig;

final class Installer extends InstallManager
{
    /**
     * Module config values to save in Configuration table
     */
    protected function configuration(): array
    {
        return SkeletonConfig::cases();
    }

    /**
     * Database tables
     */
    protected function database(): array
    {
        return [
            // 'table' => 'install_query',
        ];
    }

    /**
     * Hooks to register
     */
    protected function hooks(): array
    {
        return [
            // 'hook_name',
        ];
    }

    /**
     * Register a web service and its permissions
     */
    protected function webservice(): array
    {
        return [
            // configuration_key => value,
        ];
    }

    /**
     * Install api clients
     */
    protected function apiClients(): array
    {
        return [
            // \PrestaShop\Module\Skeleton\Api\Name\NameApi::class
        ];
    }
}