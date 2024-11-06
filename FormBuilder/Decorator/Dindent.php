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

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Form\FormBuilder\Decorator;
use Qubus\Form\FormBuilder\Element;
use Qubus\Support\Indenter;

use function class_exists;
use function str_repeat;

/**
 * Indent the HTML.
 *
 * @param int   spaces                The number of spaces.
 * @param array indentation_character Specify the indentation char(s), use instead of spaces.
 * @param bool  deep                  Whether to indent each individual child node.
 */
class Dindent extends Decorator
{
    /**
     * Dindent options
     *
     * @var array
     */
    protected array $options;

    /**
     * @param array $options
     * @param bool $deep Indent each individual child
     * @throws Exception
     */
    public function __construct(array $options = [], bool $deep = false)
    {
        if (! class_exists(Indenter::class)) {
            throw new Exception('Please add the Support library.');
        }

        if (isset($options['spaces'])) {
            $options += ['indentation_character' => str_repeat(' ', $options['spaces'])];
        }
        $this->options = $options;

        $this->deep = $deep;
    }

    /**
     * Render to HTML
     *
     * @param Element $element
     * @param string $html Original rendered html
     * @return string
     * @throws TypeException
     */
    public function render(Element $element, string $html): string
    {
        $parser = new Indenter($this->options);
        return $parser->indent($html);
    }
}
