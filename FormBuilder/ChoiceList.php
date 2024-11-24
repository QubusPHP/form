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

use function array_merge;
use function htmlentities;
use function implode;
use function in_array;
use function is_array;

/**
 * Representation of a set of radio buttons or checkboxes in a form.
 *
 * @option bool single-line  Put all items on a single line
 * @option bool add-hidden   Add hidden input so a value is send when nothing is checked
 */
class ChoiceList extends Choice
{
    /**
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @throws TypeException
     */
    public function __construct(array $options = [], array $attr = [])
    {
        parent::__construct($options, $attr);
        $this->addClass('choicelist');

        unset($this->attr['name'], $this->attr['multiple'], $this->attr['required']);
    }

    /**
     * Render the content of the HTML element.
     *
     * @return string
     */
    protected function renderContent(): string
    {
        $this->getId();
        $name = $this->getName();
        $value = $this->getValue();
        $items = $this->getItems();
        $required = $this->getOption('required');
        $type = $this->getOption('multiple') ? 'checkbox' : 'radio';

        $selectedFirst = (bool) $this->getOption('selected-first');
        $singleLine = (bool) $this->getOption('single-line');

        // Build inputs
        $inputs = $inputsFirst = [];

        foreach ($items as $key => $val) {
            $selected = ! is_array($value) ? (string) $key === (string) $value : in_array($key, $value);

            $htmlAttrs = 'type="' . $type . '" name="' . htmlentities($name) . '"'
            . 'value="' . htmlentities($key) . '"' . ($selected ? ' checked' : '') . ($required ? ' required' : '');
            $input = "<label><input $htmlAttrs> " . htmlentities($val) . "</label>";

            if (! $singleLine) {
                $input = '<div>' . $input . '</div>';
            }

            if ($selected && $selectedFirst) {
                $inputsFirst[] = $input;
            } else {
                $inputs[] = $input;
            }
        }

        $hidden = $type === 'checkbox' && $this->getOption('add-hidden') ?
        '<input type="hidden" name="' . htmlentities($name) . '" value="">' . "\n" : '';

        return $hidden . implode("\n", array_merge($inputsFirst, $inputs));
    }

    /**
     * Render the input control to HTML.
     *
     * @return string
     */
    public function renderElement(): string
    {
        if ($this->getOption('single-line')) {
            $this->addClass('choicelist-single-line');
        }

        // Build html control
        return "<div " . $this->attr->render() . ">\n"
        . $this->getContent() . "\n"
        . "</div>";
    }
}
