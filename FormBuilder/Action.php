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

use function htmlentities;

/**
 * Base class for a link or button.
 */
abstract class Action extends Element implements WithComponents
{
    use Components;

    /**
     * @param array  $options  Element options
     * @param array  $attrs    HTML attributes
     */
    public function __construct(array $options = [], array $attrs = [])
    {
        $options += ['label' => false, 'escape' => true];
        parent::__construct($options, $attrs);

        $this->initComponents();
    }

    /**
     * Get the description of the element.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getOption('description');
    }

    /**
     * Validate the element.
     *
     * @return bool
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * Render the content of the element.
     *
     * @return string
     */
    protected function renderContent(): string
    {
        $content = $this->getDescription();
        if ($this->getOption('escape')) {
            $content = htmlentities($content);
        }

        return $content;
    }

    /**
     * Render the element.
     *
     * @return string
     */
    public function renderElement(): string
    {
        $tagname = $this::TAGNAME;
        return "<{$tagname} {$this->attr}>" . $this->getContent() . "</{$tagname}>";
    }
}
