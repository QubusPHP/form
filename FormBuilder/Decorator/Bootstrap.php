<?php

declare(strict_types=1);

namespace Qubus\Form\FormBuilder\Decorator;

use Closure;
use Exception;
use Qubus\Exception\Data\TypeException;
use Qubus\Form\FormBuilder;
use Qubus\Form\FormBuilder\Button;
use Qubus\Form\FormBuilder\Control;
use Qubus\Form\FormBuilder\Decorator;
use Qubus\Form\FormBuilder\Element;
use Qubus\Form\FormBuilder\Group;
use Qubus\Form\FormBuilder\Input;
use Qubus\Form\FormBuilder\Label;
use Qubus\Form\FormBuilder\Select;
use Qubus\Form\FormBuilder\Textarea;
use Qubus\Form\FormBuilder\WithComponents;

class Bootstrap extends Decorator
{
    /**
     * Prefix for the default fontset
     * @var string
     */
    public static string $defaultFontset = 'glyphicon';


    /**
     * Class constructor
     *
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['version'])) {
            trigger_error("You should specify which version of Bootstrap is used.", E_USER_WARNING);
        } elseif ((int) $options['version'] < 3) {
            throw new Exception("Only Boostrap version 3 and later is supported.");
        }
    }

    /**
     * Whether to apply the decorator to all descendants.
     *
     * @return bool
     */
    public function isDeep(): bool
    {
        return true;
    }


    /**
     * Apply modifications
     *
     * @param Element $element
     * @param bool $deep
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function apply(Element $element, bool $deep): void
    {
        $this->applyToElement($element);

        if ($element instanceof WithComponents) {
            $this->applyToAddon('prepend', $element);
            $this->applyToAddon('append', $element);
            $this->applyToLabel($element);

            $this->applyToContainer($element);
        }
    }

    /**
     * Apply modifications to element
     *
     * @param Element $element
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    protected function applyToElement(Element $element): void
    {
        // Add boostrap style class
        if (static::isButton($element)) {
            $style = $element->getOption('btn') ?: 'default';
            $element->addClass('btn' . preg_replace('/^|\s+/', ' btn-', $style));
        } elseif (
                $element instanceof Input && !(in_array($element->getType(), ['checkbox', 'radio'])) ||
                $element instanceof Textarea ||
                $element instanceof Select
        ) {
            $element->addClass('form-control');
        }

        if ($element instanceof Input && !static::isButton($element)) {
            $element->newComponent('input-group', 'div', [], ['class' => 'input-group']);
        }

        if ($element instanceof Label) {
            $element->addClass('div-label');
        }

        if ($element instanceof WithComponents) {
            $element->newComponent(
                'help',
                'span',
                [],
                ['class' => 'help-block']
            )->setContent(Closure::bind(function () {
                return $this->getOption('help');
            }, $element));
        }
    }

    /**
     * Render prepend or append HTML.
     *
     * @param string $placement
     * @param Element|WithComponents $element
     * @return void
     */
    protected function applyToAddon(string $placement, Element|WithComponents $element): void
    {
        $addon = $element->getComponent($placement);

        if (static::isButton($element)) {
            $class = "btn-label" . ($placement === 'append' ? " btn-label-right" : '');
            $addon->addClass($class);
        } elseif ($element instanceof Input && !static::isButton($element)) {
            $class = static::isButton($element) ? 'input-group-btn' : 'input-group-addon';
            $addon->addClass($class);
        }
    }

    /**
     * Apply modifications to label
     *
     * @param Element|WithComponents $element
     * @return void
     */
    public function applyToLabel(Element|WithComponents $element): void
    {
        $label = $element->getComponent('label');
        if (!$label || $element instanceof Input && $element->attr['type'] === 'hidden') {
            return;
        }

        $labelOption = $element->getOption('label') !== 'inside' ? 'control-label' : null;
        $gridOption = $element->getOption('grid');
        $grid = $gridOption ? $gridOption[0] : '';

        $label->addClass($labelOption);

        $label->addClass($grid);
    }

    /**
     * Apply modifications to container
     *
     * @param Element|WithComponents $element
     * @return void
     */
    public function applyToContainer(Element|WithComponents $element): void
    {
        $container = $element->getComponent('container');
        if (!$container) {
            return;
        }

        $container->addClass('form-group');
        $class = $element instanceof Control && $element->getError() ? 'has-error' : '';

        $container->addClass($class);
    }


    /**
     * Render the content of the element control to HTML.
     *
     * @param Element|WithComponents $element
     * @param string $html Original rendered html
     * @return string
     */
    public function renderContent(Element|WithComponents $element, string $html): string
    {
        if (static::isButton($element) && $element->hasClass('btn-labeled')) {
            $prepend = $element->getOption('prepend');
            if ($prepend) {
                $html = $element->getComponent('prepend')->setContent($prepend) . $html;
            }

            $append = $element->getOption('append');
            if ($append) {
                $html = $element->getComponent('append')->setContent($append) . $html;
            }
        }

        return $html;
    }

    /**
     * Render the element control to HTML.
     *
     * @param Element|WithComponents $element
     * @param string $html Original rendered html
     * @return string
     * @throws \Qubus\Exception\Exception
     */
    public function render(Element|WithComponents $element, string $html): string
    {
        if (!$element instanceof withComponents) {
            return $html;
        }

        $container = $element->getComponent('container')->setContent(null);

        // Label
        $optLabel = method_exists($element, 'getLabel') ? $element->getOption('label') : null;
        if ($optLabel && $optLabel !== 'inside') {
            $container->add($element->getComponent('label'));
        }

        // Grid for horizontal form
        $optGrid = $element->getOption('grid');
        if ($optGrid) {
            $grid = $element->newComponent(null, 'div', [], ['class' => $optGrid[1]]);
            if (!$optLabel || $optLabel === 'inside') {
                $grid->addClass(preg_replace('/-(\d+)\b/', '-offset-$1', $optGrid[0]));
            }
            $container->add($grid);
        }

        // Add form-control elements
        $this->renderControl($element, $grid ?? $container);

        // Validation script
        if (method_exists($element, 'getValidationScript')) {
            $container->add($element->getValidationScript());
        }

        return (string) $container;
    }

    /**
     * Render form control
     *
     * @param Element|WithComponents $element
     * @param Group $container
     * @throws \Qubus\Exception\Exception
     */
    protected function renderControl(Element|WithComponents $element, Group $container): void
    {
        // Input group for prepend/append
        $useInputGroup = $element->getComponent('input-group') &&
        ($element->getOption('prepend') != '' || $element->getOption('append') != '') &&
        $element->getOption('label') !== 'inside';

        if ($useInputGroup) {
            $group = $element->getComponent('input-group');
            $container->add($group);
        } else {
            $group = $container; // Or just use container
        }

        // Prepend
        $labeledButton = static::isButton($element) && $element->hasClass('btn-labeled');
        if (!$labeledButton && $element->getOption('prepend')) {
            $group->add($element->getComponent('prepend'));
        }

        // Element
        $el = $element->renderElement();
        if ($element->getOption('label') === 'inside') {
            $el = $element->getComponent('label')->setContent($el);
        }
        $group->add($el);

        // Append
        if (!$labeledButton && $element->getOption('append')) {
            $group->add($element->getComponent('append'));
        }

        // Help block
        if ($element->getComponent('help') && $element->getOption('help')) {
            $container->add($element->getComponent('help'));
        }

        // Error
        if (method_exists($element, 'getError')) {
            $error = $element->getError();
            if ($error) {
                $element->begin('span', [], ['class' => 'help-block error'])->setContent($error);
            }
        }
    }


    /**
     * Check if element is a button
     *
     * @param Element $element
     * @return bool
     */
    protected static function isButton(Element $element): bool
    {
        return
        $element instanceof Button ||
        ($element instanceof Input && in_array($element->attr['type'], ['button', 'submit', 'reset'])) ||
        $element->hasClass('btn') ||
        $element->getOption('btn');
    }

    /**
     * Register Boostrap decorator and elements
     *
     * @return void
     */
    public static function register(): void
    {
        FormBuilder::$decorators['bootstrap'] = Bootstrap::class;

        FormBuilder::$elements += [
            'fileinput' =>  [FormBuilder\FileInput::class],
            'imageinput' => [FormBuilder\ImageInput::class],
        ];
    }

    /**
     * HTML for font icons (like FontAwesome)
     *
     * @param string $icon     Icon name (and other options)
     * @param string|null $fontset  Prefix for fonts
     * @return string
     */
    public static function icon(string $icon, ?string $fontset = null): string
    {
        if (!isset($fontset)) {
            $fontset = static::$defaultFontset;
        }

        $class = $fontset . preg_replace('/^|\s+/', " $fontset-", $icon);
        return '<i class="' . $class . '"></i>';
    }
}
