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
use Qubus\Exception\Exception;

use function htmlentities;
use function method_exists;

/**
 * Element that exists of several components.
 */
trait Components
{
    /**
     * Components
     *
     * @var Element[]
     */
    protected array $components = [];

    /**
     * Initialise default components.
     *
     * @throws TypeException
     * @throws Exception
     */
    protected function initComponents(): void
    {
        $this->newComponent('label', 'label')
            ->setAttr('for', function () {
                return $this->getOption('label') !== 'inside' ? $this->getId() : null;
            })
            ->setContent(function () {
                return htmlentities($this->getDescription());
            });

        $this->newComponent('prepend', 'span')->setContent(function () {
            return $this->getOption('prepend');
        });

        $this->newComponent('append', 'span')->setContent(function () {
            return $this->getOption('append');
        });

        $this->newComponent('error', 'span', [], ['class' => 'error'])->setContent(function () {
            return htmlentities($this->getError());
        });
    }

    /**
     * Create / init new component.
     *
     * @param string|null $name Component name
     * @param string|null $type Element type
     * @param array $options Element options
     * @param array $attr Element attr
     * @return Element
     * @throws Exception
     * @throws TypeException
     */
    public function newComponent(
        ?string $name = null,
        ?string $type = null,
        array $options = [],
        array $attr = []
    ): Element {
        $options += ['id' => false, 'decorate' => false];
        $component = $this->build($type, $options, $attr)->asComponentOf($this);

        if (isset($name)) {
            $this->components[$name] = $component;
        }
        return $component;
    }

    /**
     * Get a component.
     *
     * @param string $name
     * @return Element|null
     * @throws TypeException
     */
    public function getComponent(string $name): ?Element
    {
        if ($name === 'container') {
            return $this->getContainer();
        }

        return $this->components[$name] ?? null;
    }

    /**
     * Get label component.
     *
     * @return Element
     * @throws TypeException
     */
    public function getLabel(): Element
    {
        return $this->getComponent('label');
    }

    /**
     * Get the container component.
     *
     * @return Group
     * @throws TypeException
     * @throws Exception
     */
    public function getContainer(): Group
    {
        $type = $this->getOption('container') ?: 'group';

        if (! isset($this->components['container'])) {
            $this->newComponent('container', $type);
        } else {
            $this->components['container'] = $this->components['container']->convertTo($type);
        }

        return $this->components['container'];
    }

    /**
     * Render the element to HTML.
     *
     * @return string
     * @throws TypeException|Exception
     */
    public function render(): string
    {
        $container = $this->getContainer()->setContent(null);

        // Label
        if ($this->getOption('label')) {
            if ($this->getOption('label') === 'inside') {
                $el = $this->getLabel()->setContent($this->renderElement());
            } else {
                $container->add($this->getLabel());
            }
        }

        // Prepend
        if ($this->getOption('prepend')) {
            $container->add($this->getComponent('prepend'));
        }

        // Element
        if (! isset($el)) {
            $el = $this->renderElement();
        }
        $container->add($el);

        // Append
        if ($this->getOption('append')) {
            $container->add($this->getComponent('append'));
        }

        // Error
        if (method_exists($this, 'getError') && $this->getError()) {
            $container->add($this->getComponent('error'));
        }

        // Validation script
        if (method_exists($this, 'getValidationScript')) {
            $container->add($this->getValidationScript());
        }

        return (string) $container;
    }

    /**
     * Render to base HTML element.
     *
     * @return string
     */
    abstract public function renderElement(): string;
}
