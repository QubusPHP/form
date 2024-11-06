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

namespace Qubus\Form\FormBuilder\Decorator;

use Qubus\Form\FormBuilder\Decorator;
use Qubus\Form\FormBuilder\Element;

use function call_user_func;

/**
 * Simple validation decorator
 */
class SimpleValidation extends Decorator
{
    /** @var callable */
    protected $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
        $this->deep = false;
    }

    /**
     * Modify the value
     *
     * @param Element $element
     * @param mixed $isValid
     * @return mixed
     */
    public function validation(Element $element, mixed $isValid): mixed
    {
        if (! $isValid) {
            return false;
        }

        $message = null;
        $isValid = call_user_func($this->callback, $value, $message);

        if (! $isValid) {
            $element->setError($message);
        }
        return $isValid;
    }
}
