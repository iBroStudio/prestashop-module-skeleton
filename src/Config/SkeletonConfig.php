<?php
/**
 * @author    :author_name <author@domain.com>
 * @copyright Since :author_copyright_year :author_company
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

namespace PrestaShop\Module\Skeleton\Config;

use \Configuration;
use IBroStudio\ModuleHelper\Enums\Contracts\ConfigEnum;

enum SkeletonConfig: string implements ConfigEnum
{
    //case KEY_NAME = '{{SKELETON}}_KEY_NAME';

    public function default(): string
    {
        return match($this)
        {
            //self::KEY_NAME => 'default_value',
            default => throw new \Exception('Unknown configuration key'),
        };
    }

    public function get(): string|false
    {
        return Configuration::get($this->value);
    }

    public function set(string $value): bool
    {
        return Configuration::updateValue($this->value, $value);
    }

    public function delete(): bool
    {
        return Configuration::deleteByName($this->value);
    }
}
