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
 * Element that exists of several components.
 */
interface WithComponents
{
    /**
     * Initialise component
     *
     * @param string|null $name Component name
     * @param string|null $type Element type
     * @param array $options Element options
     * @param array $attr Element attr
     * @return Element $this
     */
    public function newComponent(
        ?string $name = null,
        ?string $type = null,
        array $options = [],
        array $attr = []
    ): Element;

    /**
     * Get a component.
     *
     * @param string $name
     * @return Element|null
     */
    public function getComponent(string $name): ?Element;

    /**
     * Render to base HTML element.
     *
     * @return string
     */
    public function renderElement(): string;
}
