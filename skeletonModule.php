<?php
/**
 * @author    :author_name <author@domain.com>
 * @copyright Since :author_copyright_year :author_company
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\Module\Skeleton\Install\Installer;

class SkeletonModule extends Module
{
    const VERSION = '1.0.0';

    public function __construct()
    {
        $this->name = ':module_filename';
        $this->tab = ':module_category';
        $this->version = static::VERSION;
        $this->author = ':vendor_name';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = false;

        parent::__construct();

        $this->displayName = $this->trans(':module_name', [], 'Modules.:translation_key.Admin');
        $this->description = $this->trans(':package_description', [], 'Modules.:translation_key.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.:translation_key.Admin');
    }

    public function install(): bool
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return Installer::install($this)
            && parent::install();
    }

    public function uninstall(): bool
    {
        return Installer::uninstall($this)
            && parent::uninstall();
    }
}