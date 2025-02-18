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

use function in_array;

/**
 * Representation of an <input> element in a form.
 *
 * @option string    type         HTML5 input type
 * @option bool      placeholder  Use a placeholder
 * @option bool      multiple     Allow multiple files (only for type="file")
 * @option bool      hidden       Add hidden input for checkbox
 * @option int|float min          Minimum value
 * @option int|float max          Maximum value
 * @option int       minlength    Minimum string length
 * @option int       maxlength    Maximum string length
 * @option string    pattern      Regexp pattern that value should match
 * @option Control   match        Match value of other element (retype password)
 * @todo Support multiple file upload
 */
class Input extends Control
{
    /**
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @throws TypeException
     */
    public function __construct(array $options = [], array $attr = [])
    {
        if (! isset($options['type'])) {
            $options['type'] = 'text';
        }
        $type = $options['type'];

        $options += $this->getDefaultOptions($type);

        if (! isset($attr['type'])) {
            $attr['type'] = function () {
                return $this->getType();
            };
        }

        if ($type === 'checkbox' || $type === 'radio') {
            if (! isset($attr['value'])) {
                $attr['value'] = 1;
            }
            if (! isset($attr['checked'])) {
                $attr['checked'] = function () {
                    return (bool) $this->getValue();
                };
            }
        }

        if (in_array($type, ['button', 'submit', 'reset']) && ! isset($attr['value'])) {
            $attr['value'] = function () {
                return $this->getDescription();
            };
        }

        $noPlaceholder = ['hidden', 'button', 'submit', 'reset', 'checkbox', 'radio', 'file'];
        if (! in_array($type, $noPlaceholder) && ! isset($attr['placeholder'])) {
            $attr['placeholder'] = function () {
                $use = $this->getOption('placeholder');
                if (! isset($use)) {
                    $use = ! $this->getOption('label');
                }
                return $use ? $this->getDescription() : null;
            };
        }

        foreach (['min', 'max', 'maxlength', 'pattern'] as $opt) {
            $attr[$opt] = function () use ($opt) {
                return $this->getOption($opt);
            };
        }

        parent::__construct($options, $attr);
    }

    /**
     * Get default options for a specific type
     */
    protected function getDefaultOptions(string $type): array
    {
        $options = [];

        switch ($type) {
            case 'hidden':
                if (! isset($this->options['label'])) {
                    $options['label'] = false;
                }
                if (! isset($this->options['container'])) {
                    $options['container'] = false;
                }
                break;

            case 'checkbox':
            case 'radio':
                if (! isset($this->options['label'])) {
                    $options['label'] = 'inside';
                }
                break;

            case 'button':
            case 'submit':
            case 'reset':
                if (! isset($this->options['label'])) {
                    $options['label'] = false;
                }
                break;
        }

        return $options;
    }

    /**
     * Get HTML5 input type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->getOption('type');
    }

    /**
     * Validate the input control.
     *
     * @return bool
     */
    protected function validate(): bool
    {
        if (! $this->validateRequired()) {
            return false;
        }

        // Empty and not required, means no further validation
        if ($this->getValue() === null || $this->getValue() === '') {
            return true;
        }

        if ($this->getType() === 'file') {
            return $this->validateUpload();
        }

        if (! $this->validateType()) {
            return false;
        }
        if (! $this->validateMinMax()) {
            return false;
        }
        if (! $this->validateLength()) {
            return false;
        }
        if (! $this->validatePattern()) {
            return false;
        }

        if (! $this->validateMatch()) {
            return false;
        }

        return true;
    }

    /**
     * Render the <input>.
     *
     * @return string
     */
    public function renderElement(): string
    {
        $el = "<input {$this->attr}>";

        // Add hidden input for checkbox
        if ($this->attr['type'] === 'checkbox' && $this->getOption('add-hidden')) {
            $el = '<input type="hidden" value="" ' . $this->attr->renderOnly(['name']) . '>' . "\n" . $el;
        }

        return $el;
    }
}
