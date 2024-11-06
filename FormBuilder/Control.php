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

use function is_array;
use function preg_replace;
use function strlen;
use function trim;
use function ucfirst;

/**
 * Base class of form control elements.
 *
 * @option description
 * @option required         Value is required
 * @option required-suffix  Suffix added to label if required
 * @option validate         Perform server side validation (default true)
 * @option container        Element type for container
 * @option label            Display a label (true, false or 'inside')
 */
abstract class Control extends Element implements WithComponents
{
    use BasicValidation;
    use Components;

    /**
     * Control value
     */
    protected mixed $value = null;

    /**
     * Error message
     *
     * @var null|string
     */
    protected ?string $error = null;

    /**
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @throws TypeException
     */
    public function __construct(array $options = [], array $attr = [])
    {
        if (! isset($attr['name'])) {
            $attr['name'] = function () {
                return $this->getName() . ($this->getOption('multiple') ? '[]' : '');
            };
        }

        if (! isset($attr['value'])) {
            $attr['value'] = function () {
                return $this->getValue();
            };
        }

        if (! isset($attr['required'])) {
            $attr['required'] = function () {
                return $this->getOption('required');
            };
        }

        parent::__construct($options, $attr);

        $this->initComponents();
    }

    /**
     * Get the description of the element.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getOption('description') ?:
        ucfirst(preg_replace(['/^.+[\.\[]|\]/', '/[_-]/'], ['', ' '], $this->getName()));
    }

    /**
     * Set the value of the element.
     *
     * @param mixed $value
     * @return mixed
     */
    public function setValue(mixed $value): mixed
    {
        foreach ($this->getDecorators() as $decorator) {
            $value = $decorator->filter($this, $value);
        }

        return $this->value = $value;
    }

    /**
     * Get the value of the element.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set the error message.
     *
     * @param string $error  The error message
     * @return void
     */
    public function setError(string $error): void
    {
        $this->error = trim($this->parse($error));
    }

    /**
     * Get the error message (after validation).
     *
     * @return string
     */
    public function getError(): string|null
    {
        return $this->error;
    }

    /**
     * Get a value for a placeholder.
     *
     * @param string $var
     * @return string
     */
    protected function resolvePlaceholder($var): string
    {
        // preg_replace callback
        if (is_array($var)) {
            $var = $var[1];
        }

        switch ($var) {
            case 'value':
                $var = (string) $this->getValue();
                break;

            case 'length':
                $var = strlen($this->getValue());
                break;

            case 'desc':
                $var = $this->getDescription();
                break;
        }

        return parent::resolvePlaceholder($var);
    }
}
