<?php

/**
 * Qubus\Form
 *
 * @link       https://github.com/QubusPHP/form
 * @copyright  2023
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Form\FormBuilder;

use function array_combine;
use function is_array;

/**
 * Representation of a control with items in a form.
 *
 * @option items            Key/value pairs used to create <option> list
 * @option selected-first   Put the selected option(s) on top of the list
 * @option multiple         Allow multiple items to be selected
 * @option use-values       Use item value as key and value
 */
abstract class Choice extends Control
{
    /**
     * Return list items
     *
     * @return array
     */
    public function getItems(): array
    {
        $items = $this->getOption('items') ?: [];
        if ($this->getOption('use-values')) {
            $items = array_combine($items, $items);
        }

        return $items;
    }

    /**
     * Set the value of the control.
     *
     * @param mixed $value
     * @return Control $this
     */
    public function setValue(mixed $value): Control
    {
        if ($this->getOption('multiple') && ! is_array($value)) {
            $value = (string) $value === '' ? [] : (array) $value;
        }

        return parent::setValue($value);
    }

    /**
     * Validate the control.
     *
     * @return bool
     */
    public function validate(): bool
    {
        return $this->validateRequired();
    }
}
