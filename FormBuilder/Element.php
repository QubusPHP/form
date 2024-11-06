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

use Closure;
use DateTime;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Form\Form;
use Qubus\Form\FormBuilder;

use function base_convert;
use function call_user_func_array;
use function func_get_args;
use function is_a;
use function is_array;
use function is_string;
use function method_exists;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function strtr;
use function substr;
use function trigger_error;
use function ucwords;
use function uniqid;

use const E_USER_WARNING;

/**
 * Base class for HTML elements.
 *
 * @option id    Element id
 * @option name  Element name
 */
abstract class Element
{
    /**
     * Element to use as factory
     *
     * @var Element|null
     */
    protected ?Element $builder = null;

    /**
     * Overwrite element types for factory method.
     * Only if this is the builder element.
     *
     * @var array
     */
    protected array $customTypes = [];

    /**
     * Element of which this is a component of.
     */
    protected $componentOf = null;

    /**
     * Parent element
     */
    protected $parent = null;

    /**
     * Decorators
     *
     * @var Decorator[]
     */
    protected array $decorators = [];

    /**
     * Element options
     *
     * @var array
     */
    protected array $options = [];

    /**
     * HTML attributes
     *
     * @var Attr[]
     */
    public Attr|array $attr = [];

    /**
     * The HTML content
     *
     * @var string|Closure|null
     */
    protected string|Closure|null $content = null;

    /**
     * @param array $options  Element options
     * @param array $attr     HTML attributes
     */
    public function __construct(array $options = [], array $attr = [])
    {
        if (! isset($attr['id'])) {
            $attr['id'] = function () {
                return $this->getId();
            };
        }

        $this->options = $options + $this->options + ['decorate' => true];
        foreach ($this->options as $key => $value) {
            if (! isset($value)) {
                unset($this->options[$key]);
            }
        }

        $this->attr = new Attr($attr + $this->attr);
    }

    /**
     * Add a decorator to the element.
     *
     * @param string|Decorator $decorator  Decorator object or name
     * @param mixed            $_          Additional arguments are passed to the constructor
     * @return Element  $this
     */
    public function addDecorator(string|Decorator $decorator): self
    {
        if (! $decorator instanceof Decorator) {
            $args = func_get_args();
            $decorator = call_user_func_array([FormBuilder::class, 'decorator'], $args);
        }

        $decorator->apply($this, false);
        $this->decorators[] = $decorator;

        return $this;
    }

    /**
     * Apply decorators from parent
     *
     * @param Group $parent
     */
    protected function applyDeepDecorators(Group $parent): void
    {
        if ($this->getOption('decorate') === false) {
            return;
        }

        foreach ($parent->getDecorators() as $decorator) {
            if ($decorator->isDeep()) {
                $decorator->apply($this, true);
            }
        }
    }

    /**
     * Get all decorators
     *
     * @return Decorator[]
     */
    public function getDecorators(): array
    {
        $decorators = $this->decorators;

        if ($this->getOption('decorate') !== false && $this->getParent()) {
            foreach ($this->getParent()->getDecorators() as $decorator) {
                if ($decorator->isDeep()) {
                    $decorators[] = $decorator;
                }
            }
        }

        return $decorators;
    }

    /**
     * Convert custom (form specific) type to general factory type.
     *
     * @param string $type    (in/out) Element type
     * @param array  $options (in/out) Element options
     * @param array  $attr    (in/out) HTML attributes
     */
    protected function convertCustomType(&$type, array &$options, array &$attr = []): void
    {
        if (isset($this->builder)) {
            $this->builder->convertCustomType($type, $options, $attr);
            return;
        }

        if (isset($this->customTypes[$type])) {
            $custom = $this->customTypes[$type];
            $type = $custom[0];
            if (isset($custom[1])) {
                $options = $custom[1] + $options;
            }
            if (isset($custom[2])) {
                $attr = $custom[2] + $attr;
            }
        }
    }

    /**
     * Factory method
     *
     * @param string $type Element type
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @return Element
     * @throws TypeException
     * @throws Exception
     */
    public function build(string $type, array $options = [], array $attr = []): Element
    {
        if (isset($this->builder)) {
            return $this->builder->build($type, $options, $attr);
        }

        $this->convertCustomType($type, $options, $attr);

        if (is_string($type) && $type[0] === ':') {
            $method = 'build' . str_replace(
                ' ',
                '',
                ucwords(preg_replace('/[^a-zA-Z0-9]/', ' ', substr($type, 1)))
            );
            if (! method_exists($this, $method)) {
                throw new Exception(
                    sprintf(
                        'Unknown field `%s`.',
                        substr($type, 1)
                    )
                );
            }
            return $this->$method(null, $options, $attr);
        }

        $element = FormBuilder::element($type, $options, $attr);
        $element->builder = $this;

        return $element;
    }

    /**
     * Get the form to which this element is added.
     *
     * @return Group|Element|Form|null
     */
    public function getForm(): Group|Element|Form|null
    {
        $parent = $this->componentOf ?: $this->getParent();
        while ($parent && ! $parent instanceof Form) {
            $parent = $parent->getParent();
        }

        return $parent;
    }

    /**
     * Set element of which this a component of
     *
     * @return Element $this
     */
    protected function asComponentOf(Element $element): static
    {
        $this->componentOf = $element;
        if (! isset($this->builder)) {
            $this->builder = $element->builder ?: $element;
        }

        return $this;
    }

    /**
     * Check if element is used as a component
     *
     * @return bool
     */
    public function isComponent(): bool
    {
        return isset($this->componentOf);
    }

    /**
     * Set parent element
     *
     * @return Element $this
     * @throws Exception
     */
    protected function setParent(Element $parent): static
    {
        if ($parent === $this) {
            throw new Exception(
                sprintf(
                    "Parent can't be element itself for `%s`.",
                    $this->getName()
                )
            );
        }

        if (isset($this->parent)) {
            $this->parent->remove($this);
        }

        $this->parent = $parent;
        if (! isset($this->builder)) {
            $this->builder = $parent->builder ?: $parent;
        }

        return $this;
    }

    /**
     * Return parent element
     *
     * @return Group|null
     */
    public function getParent(): ?Group
    {
        return $this->parent;
    }

    /**
     * Get parent or element of which this is an component.
     *
     * @return Element|Group|null
     */
    public function end(): Element|Group|null
    {
        return $this->componentOf ?: $this->getParent();
    }

    /**
     * Get element id.
     *
     * @return string
     */
    public function getId(): string
    {
        if (! isset($this->options['id'])) {
            $form = $this->getForm();

            if ($form) {
                $name = $this->getName();
                $id = $this->getForm()->getId() . '-' . ($name ?
                    preg_replace('/[^\w\-]/', '', strtr($name, '[.', '--')) :
                    base_convert(uniqid(), 16, 32));
            } else {
                $id = base_convert(uniqid(), 16, 32);
            }

            $this->options['id'] = $id;
        }

        return (string) $this->options['id'];
    }

    /**
     * Return the name of the control.
     *
     * @return bool|string|null
     */
    public function getName(): bool|string|null
    {
        return $this->getOption('name');
    }

    /**
     * Set HTML attribute(s).
     *
     * @param array|string $attr   Attribute name or assoc array with attributes
     * @param mixed|null $value
     * @return Element $this
     */
    final public function setAttr(array|string $attr, mixed $value = null): static
    {
        $this->attr->set($attr, $value);
        return $this;
    }

    /**
     * Get an HTML attribute(s).
     * All attributes will be cased to their string representation.
     *
     * @param string|null $attr Attribute name, omit to get all attributes
     * @return mixed
     */
    final public function getAttr(string $attr = null): mixed
    {
        return $this->attr->get($attr);
    }

    /**
     * Check if class is present
     *
     * @param string $class
     * @return bool
     */
    final public function hasClass(string $class): bool
    {
        return $this->attr->hasClass($class);
    }

    /**
     * Add a class
     *
     * @param array|string $class  Multiple classes may be specified as array or using a space
     * @return Element $this
     */
    final public function addClass(array|string $class): static
    {
        $this->attr->addClass($class);
        return $this;
    }

    /**
     * Remove a class
     *
     * @param array|string $class  Multiple classes may be specified as array or using a space
     * @return Element $this
     */
    public function removeClass(array|string $class): static
    {
        $this->attr->removeClass($class);
        return $this;
    }

    /**
     * Set an option or array with options
     *
     * @param array|string $option  Option name or array with options
     * @param mixed|null $value
     * @return Element $this
     */
    public function setOption(array|string $option, mixed $value = null): static
    {
        if ($option === 'decorate' && $this->getParent()) {
            $name = $this->getName();
            trigger_error(
                sprintf(
                    "You should set the 'decorate' option before adding %s to a form or group.",
                    $name ? "element '$name'" : "an element"
                ),
                E_USER_WARNING
            );
        }

        if (is_array($option)) {
            foreach ($option as $key => $value) {
                if (! isset($value)) {
                    unset($this->options[$key]);
                } else {
                    $this->options[$key] = $value;
                }
            }
        } elseif (! isset($value)) {
            unset($this->options[$option]);
        } else {
            $this->options[$option] = $value;
        }

        return $this;
    }

    /**
     * Get an option.
     *
     * @param string $option
     * @return mixed
     */
    public function getOption(string $option): mixed
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        if (isset($this->parent)) {
            return $this->parent->getOption($option, true);
        }
        if (isset(FormBuilder::$options[$option])) {
            return FormBuilder::$options[$option];
        }

        return null; // not found
    }

    /**
     * Get all options.
     * Bubbles to combine options of parent/ancestors.
     *
     * @return array
     */
    public function getOptions(): array
    {
        $defaults = isset($this->parent) ? $this->parent->getOptions() : FormBuilder::$options;
        return $this->options + $defaults;
    }

    /**
     * Validate the element.
     */
    final public function isValid(): bool
    {
        if ($this->getOption('validate') === false) {
            return true;
        }

        $valid = $this->validate();

        // Apply changes to options
        foreach ($this->getDecorators() as $decorator) {
            $valid = $decorator->validate($this, $valid);
        }

        return $valid;
    }

    /**
     * Standard validation for the element
     *
     * @return bool
     */
    protected function validate(): bool
    {
        return true;
    }

    /**
     * Set element content
     *
     * @param Closure|string $content Content as HTML
     * @return $this
     */
    public function setContent(Closure|string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the element content
     *
     * @return string|null
     */
    final public function getContent(): ?string
    {
        $content = $this->renderContent();

        foreach ($this->getDecorators() as $decorator) {
            $content = $decorator->renderContent($this, $content);
        }

        return $content;
    }

    /**
     * Render the element content
     *
     * @return string|null
     */
    protected function renderContent(): ?string
    {
        if (! isset($this->content)) {
            return null;
        }

        $content = $this->content;
        if ($content instanceof Closure) {
            $content = $content();
        }

        return (string) $content;
    }

    /**
     * Render the element
     *
     * @return string
     */
    protected function render(): string
    {
        $tagname = $this::TAGNAME;
        return "<{$tagname} {$this->attr}>" . $this->getContent() . "</{$tagname}>";
    }

    /**
     * Render the element to HTML.
     *
     * @return string
     */
    final public function toHTML(): string
    {
        $html = $this->render();

        foreach ($this->getDecorators() as $decorator) {
            $html = $decorator->render($this, $html);
        }

        return $html;
    }

    /**
     * Render the element to HTML.
     *
     * @return string
     */
    final public function __toString()
    {
        return $this->toHTML();
    }

    /**
     * Parse a message, inserting values for placeholders.
     *
     * @param string $message
     * @return string
     */
    public function parse(string $message): string
    {
        return preg_replace_callback('/{{\s*([^}])++\s*}}/', [$this, 'resolvePlaceholder'], $message);
    }

    /**
     * Get a value for a placeholder
     *
     * @param string|array $var
     * @return string
     */
    protected function resolvePlaceholder(string|array $var): string
    {
        // preg_replace callback
        if (is_array($var)) {
            $var = $var[1];
        }

        $value = $this->getOption($var);
        if (! isset($value)) {
            $value = $this->getAttr($var);
        }

        if ($value instanceof Control) {
            return $value->getValue();
        }
        if ($value instanceof DateTime) {
            return date('%x', $value->getTimestamp());
        }
        return (string) $value;
    }

    /**
     * Convert an element to another type.
     * Simply returns $this if element is already of correct type.
     *
     * @param string $type
     * @return Element
     */
    public function convertTo(string $type): Element|static
    {
        $options = [];
        $attr = [];
        $this->convertCustomType($type, $options, $attr);

        if (isset(FormBuilder::$elements[$type]) && is_a($this, FormBuilder::$elements[$type][0])) {
            return $this;
        }

        try {
            $new = $this->build($type);
        } catch (TypeException | Exception $e) {
            die($e->getMessage());
        }

        foreach ($this as $prop => $value) {
            $new->$prop = $value;
        }

        $new->setOption($options);
        $new->setAttr($attr);

        $new->onClone();

        if (isset($this->parent)) {
            foreach ($this->parent->children as &$child) {
                if ($child === $this) {
                    $child = $new;
                }
            }
        }

        return $new;
    }

    /**
     * Magic method called after cloning element
     */
    public function __clone()
    {
        $this->onClone();
    }

    /**
     * Method called after cloning on copying element
     */
    protected function onClone(): void
    {
        foreach ($this as &$value) {
            if ($value instanceof Closure) {
                $value->bindTo($this);
            }
        }

        foreach ($this->attr as &$value) {
            if ($value instanceof Closure) {
                $value->bindTo($this);
            }
        }

        foreach ($this->attr['class'] as &$value) {
            if ($value instanceof Closure) {
                $value->bindTo($this);
            }
        }
    }
}
