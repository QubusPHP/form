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

use Qubus\Exception\Data\TypeException;

use function htmlentities;

/**
 * Representation of a <textarea> element.
 */
class Textarea extends Control
{
    /**
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @throws TypeException
     */
    public function __construct(array $options = [], array $attr = [])
    {
        if (! isset($attr['placeholder'])) {
            $attr['placeholder'] = fn () => $this->getOption('label') ? null : $this->getDescription();
        }

        parent::__construct($options, $attr);
    }

    /**
     * Validate the textarea control.
     *
     * @return bool
     */
    public function validate(): bool
    {
        if (! $this->validateRequired()) {
            return false;
        }

        // Empty and not required, means no further validation
        if ($this->getValue() === null || $this->getValue() === '') {
            return true;
        }

        if (! $this->validateLength()) {
            return false;
        }

        return true;
    }

    /**
     * Render the <textarea>
     *
     * @return string
     */
    public function renderElement(): string
    {
        return "<textarea {$this->attr}>" . htmlentities($this->getOption('value') ?? '') . "</textarea>";
    }
}
