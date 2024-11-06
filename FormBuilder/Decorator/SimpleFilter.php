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
 * Simple filter decorator
 */
class SimpleFilter extends Decorator
{
    /** @var callable */
    protected $callback;

    /**
     * @param callable $callback
     * @param bool $deep      Apply filter to children
     */
    public function __construct(callable $callback, bool $deep = false)
    {
        $this->callback = $callback;
        $this->deep = $deep;
    }

    /**
     * Modify the value
     *
     * @param Element $element
     * @param mixed $value
     * @return mixed
     */
    public function filter(Element $element, mixed $value): mixed
    {
        return call_user_func($this->callback, $value);
    }
}
