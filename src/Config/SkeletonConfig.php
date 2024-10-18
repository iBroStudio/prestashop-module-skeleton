<?php
/**
 * @author    :author_name <author@domain.com>
 * @copyright Since :author_copyright_year :author_company
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

namespace PrestaShop\Module\Skeleton\Config;

use IBroStudio\ModuleHelper\Enums\Contracts\ConfigContract;
use IBroStudio\ModuleHelper\Exceptions\ValidationException;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

enum SkeletonConfig: string implements ConfigContract
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

    public function field(): array
    {
        return match ($this) {
            /*
            self::KEY_NAME => [
                'child' => $this->value,
                'type' => TextType::class,
                'options' => [
                    'label' => 'Label',
                    'help' => 'Helper text',
                ]
            ],
            */
            default => throw new \Exception('Unknown configuration key'),
        };
    }

    public static function group(string $group): array
    {
        return match($group)
        {
            'group_name' => [
                //self::KEY_NAME,
            ],
            default => throw new \Exception('Unknown configuration group'),
        };
    }

    public static function values(): array
    {
        $values = \Configuration::getMultiple(
            array_column(self::cases(), 'value')
        );

        foreach ($values as $key => $value) {
            if (
                ($field = self::from($key)->field())
                && array_key_exists('getter', $field)
            ) {
                $values[$key] = $field['getter']($value);
            }
        }

        return $values;
    }

    public function validate(mixed $value): self
    {
        if (
            ($field = $this->field())
            && array_key_exists('validate', $field)
            && ! $field['validate']($value)
        ) {
            throw new ValidationException("{$field['options']['label']}: \"{$value}\" is invalid");
        }

        return $this;
    }

    public function get(): mixed
    {
        if (
            ($field = $this->field())
            && array_key_exists('getter', $field)
        ) {
            return $field['getter'](\Configuration::get($this->value));
        }

        return \Configuration::get($this->value);
    }

    public function set(mixed $value): bool
    {
        if (
            ($field = $this->field())
            && array_key_exists('setter', $field)
        ) {
            return \Configuration::updateValue($this->value, $field['setter']($value));
        }

        return \Configuration::updateValue($this->value, $value);
    }

    public function delete(): bool
    {
        return \Configuration::deleteByName($this->value);
    }
}
