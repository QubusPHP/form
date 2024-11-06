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
 * Representation of an HTML <fieldset>.
 *
 * @option legend  <legend> of the fieldset
 */
class Fieldset extends Group
{
    /** @var string */
    public const TAGNAME = 'fieldset';

    protected Legend $legend;

    /**
     * @param array  $options  Element options
     * @param array  $attr     HTML attributes
     */
    public function __construct(array $options = [], array $attr = [])
    {
        parent::__construct($options, $attr);
    }

    /**
     * Get the legend of the fieldset.
     *
     * @return Legend
     */
    public function getLegend(): Legend
    {
        if (! isset($this->legend)) {
            $this->legend = new Legend();
        }

        return $this->legend->setContent(function () {
            return $this->getOption('legend');
        });
    }

    /**
     * Render the fieldset to HTML.
     *
     * @return string
     */
    public function open(): string
    {
        return "<fieldset {$this->attr}>" . $this->getLegend();
    }
}
