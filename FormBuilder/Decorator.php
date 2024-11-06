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

/**
 * Decorator base class
 */
abstract class Decorator
{
    /**
     * Whether to indent each individual child node.
     */
    protected bool $deep = false;

    /**
     * Whether to apply the decorator to all descendants.
     */
    public function isDeep(): bool
    {
        return $this->deep;
    }

    /**
     * Apply modifications.
     *
     * @param bool $deep    The decorator of a parent is applied to a child.
     */
    public function apply(Element $element, bool $deep)
    {
    }

    /**
     * Validate the element
     *
     * @param Element $element
     * @param bool $valid Result of FormBuilder validation.
     * @return bool
     */
    public function validate(Element $element, bool $valid): bool
    {
        return $valid;
    }

    /**
     * Modify the value.
     *
     * @param Element $element
     * @param mixed $value
     * @return mixed
     */
    public function filter(Element $element, mixed $value): mixed
    {
        return $value;
    }

    /**
     * Render to HTML
     *
     * @param Element $element
     * @param string $html Original rendered html.
     * @return string
     */
    public function render(Element $element, string $html): string
    {
        return $html;
    }

    /**
     * Render the element content to HTML
     *
     * @param Element $element
     * @param string $html Original rendered html.
     * @return string
     */
    public function renderContent(Element $element, string $html): string
    {
        return $html;
    }

    public function applyToValidationScript(Control $param, array $rules)
    {
    }
}
