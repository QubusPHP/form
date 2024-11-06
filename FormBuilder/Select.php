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
use function sprintf;

/**
 * Representation of a <select> element.
 *
 * @option string placeholder  First <option> with empty value
 */
class Select extends Choice
{
    /**
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @throws TypeException
     */
    public function __construct(array $options = [], array $attr = [])
    {
        if (! isset($attr['multiple'])) {
            $attr['multiple'] = function () {
                return (bool) $this->getOption('multiple');
            };
        }

        return parent::__construct($options, $attr);
    }

    /**
     * Render the content of the HTML element.
     *
     * @return string|null
     */
    protected function renderContent(): ?string
    {
        $items = $this->getItems();
        $value = $this->getValue();
        $selectedFirst = (bool) $this->getOption('selected-first');

        $opts = $optsFirst = [];

        $placeholder = $this->getOption('placeholder');
        if ($placeholder !== false) {
            $selected = ! isset($value) || $value === '';
            $optsFirst[] = "<option value=\"\"" . ($selected ? ' selected' : '') . " disabled>"
            . htmlentities($placeholder) . "</option>\n";
        }

        foreach ($items as $key => $val) {
            $selected = ! is_array($value) ? (string) $key === (string) $value : in_array($key, $value);

            $opt = sprintf(
                '<option value="%s" %s>%s</option>',
                htmlentities($key),
                $selected ? ' selected' : '',
                htmlentities($val)
            ) . "\n";

            if ($selected && $selectedFirst) {
                $optsFirst[] = $opt;
            } else {
                $opts[] = $opt;
            }
        }

        return implode("\n", array_merge($optsFirst, $opts));
    }

    /**
     * Render the <select>
     *
     * @return string
     */
    public function renderElement(): string
    {
        return "<select {$this->attr}>\n" . $this->getContent() . "\n</select>";
    }
}
