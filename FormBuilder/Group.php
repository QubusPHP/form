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
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

use function array_merge;
use function call_user_func_array;
use function func_get_args;
use function gettype;
use function implode;
use function is_string;
use function sprintf;
use function substr;

/**
 * Base class for an HTML element with children.
 */
class Group extends Element
{
    /**
     * The HTML tag name.
     *
     * @var string
     */
    public const TAGNAME = null;

    /**
     * Child nodes of the group.
     *
     * @var array
     */
    protected array $children = [];

    /**
     * Add a decorator to the element.
     *
     * @param string|Decorator $decorator Decorator object or name
     * @param mixed            $_         Additional arguments are passed to the constructor
     * @return $this
     */
    public function addDecorator(string|Decorator $decorator): self
    {
        call_user_func_array([parent::class, 'addDecorator'], func_get_args());

        foreach ($this->getChildren() as $child) {
            if ($this->getOption('decorate') === false) {
                continue;
            }
            $decorator->apply($child, true);
        }

        return $this;
    }

    /**
     * Apply decorators from parent.
     *
     * @param Group $parent
     */
    protected function applyDeepDecorators(Group $parent): void
    {
        if ($this->getOption('decorate') === false) {
            return;
        }

        parent::applyDeepDecorators($parent);

        foreach ($this->getChildren() as $child) {
            $child->applyDeepDecorators($parent);
        }
    }

    /**
     * Add a child to the group.
     *
     * @param string|Element $child
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @return Group $this
     * @throws Exception
     */
    public function add(Element|string $child, array $options = [], array $attr = []): static
    {
        if (! isset($child) || '' === $child) {
            return $this;
        }

        if (is_string($child) && $child[0] !== '<') {
            $child = $this->build($child, $options, $attr);
        }

        if ($child instanceof Element) {
            $child->setParent($this);
        }
        $this->children[] = $child;

        if ($child instanceof Element) {
            $child->applyDeepDecorators($this);
        }

        return $this;
    }

    /**
     * Add a child and return it.
     *
     * @param string|Element $child
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @return Element $child
     * @throws TypeException|Exception
     */
    public function begin(Element|string $child, array $options = [], array $attr = []): Element
    {
        if (is_string($child) && $child[0] !== '<') {
            $child = $this->build($child, $options, $attr);
        }

        if (! $child instanceof Element) {
            throw new TypeException(
                sprintf(
                    'To add a `%s` use the add() method.',
                    gettype($child)
                )
            );
        }

        $this->add($child);
        return $child;
    }

    /**
     * Get the children of the group.
     *
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Find a specific child through deep search.
     *
     * @param Element|string $element Element name or #id
     * @param bool $unlink Unlink the found element
     * @return Element|null
     */
    protected function deepSearch(Element|string $element, bool $unlink = false): ?Element
    {
        if (is_string($element)) {
            if ($element[0] === '#') {
                $id = substr($element, 1);
            } else {
                $name = $element;
            }
        }

        $found = null;
        foreach ($this->children as $i => $child) {
            if (! $child instanceof Element) {
                continue;
            }

            if (isset($id)) {
                if ($child->getId() === $id) {
                    $found = $child;
                }
            } elseif (isset($name)) {
                if ($child->getName() === $name) {
                    $found = $child;
                }
            } elseif (isset($element)) {
                if ($child === $element) {
                    $found = $child;
                }
            }

            if ($found && $unlink) {
                unset($this->children[$i]);
            }

            if (! $found && $child instanceof Group) {
                $found = $child->deepSearch($element);
            }

            if (isset($found)) {
                return $found;
            }
        }

        return null; // Not found
    }

    /**
     * Get a specific child (deep search).
     *
     * @param string $name Element name or #id
     * @return Element|null
     */
    public function get(string $name): ?Element
    {
        return $this->deepSearch($name);
    }

    /**
     * Checks if a specific child exists.
     *
     * @param string $name Element name or id.
     * @return bool
     */
    public function has(string $name): bool
    {
        return null !== $this->get($name);
    }

    /**
     * Get all the form elements in the group (deep search).
     *
     * @return Control[]
     */
    public function getControls(): array
    {
        $elements = [];

        foreach ($this->children as $child) {
            if ($child instanceof Control) {
                $name = $child->getName();
                if ($name) {
                    $elements[$name] = $child;
                } else {
                    $elements[] = $child;
                }
            } elseif ($child instanceof Group) {
                $elements = array_merge($elements, $child->getControls());
            }
        }

        return $elements;
    }

    /**
     * Remove a specific child (deep search)
     *
     * @param Element|string $element  Element, element name or #id.
     * @return Group $this
     */
    public function remove(Element|string $element): static
    {
        if ($this->deepSearch($element, true)) {
            if (is_string($element)) {
                $element = '';
            }
            if (is_object($element)) {
                $element->parent = null;
            }
        }

        return $this;
    }

    /**
     * Set the element content.
     *
     * @param Closure|string|null $content
     * @return $this
     */
    public function setContent(Closure|string|null $content = null): static
    {
        foreach ($this->children as $child) {
            if ($child instanceof Element) {
                $child->parent = null;
            }
        }

        $this->children = $content ? [$content] : [];
        return $this;
    }

    /**
     * Set the values of the elements.
     *
     * @param array $values
     * @return Group  $this
     */
    public function setValues(array $values): static
    {
        $values = (array) $values;

        foreach ($this->getControls() as $element) {
            $name = $element->getName();
            if ($name && isset($values[$name])) {
                $element->setValue($values[$name]);
            }
        }

        return $this;
    }

    /**
     * Get the values of the elements.
     *
     * @return array
     */
    public function getValues(): array
    {
        $values = [];

        foreach ($this->getControls() as $element) {
            if ($element->getName()) {
                $values[$element->getName()] = $element->getValue();
            }
        }

        return $values;
    }

    /**
     * Validate the elements in the group.
     *
     * @return bool
     */
    protected function validate(): bool
    {
        $ret = true;

        foreach ($this->children as $child) {
            if (! $child instanceof Element || $child->getOption('validation') === false) {
                continue;
            }
            $ret = $ret && $child->isValid();
        }

        return $ret;
    }

    /**
     * Render the opening tag
     *
     * @return string|null
     */
    public function open(): ?string
    {
        if (isset($this->options['form-tag']) && $this->options['form-tag'] === false) {
            return '';
        }
        $tagname = $this::TAGNAME;
        return $tagname ? "<{$tagname} {$this->attr}>" : null;
    }

    /**
     * Render the closing tag
     *
     * @return string|null
     */
    public function close(): ?string
    {
        if (isset($this->options['form-tag']) && $this->options['form-tag'] === false) {
            return '';
        }

        $tagname = $this::TAGNAME;
        return $tagname ? "</{$tagname}>" : null;
    }

    /**
     * Render the content of the HTML element.
     *
     * @return string|null
     */
    protected function renderContent(): ?string
    {
        $items = [];

        foreach ($this->children as $child) {
            if (! isset($child) || ($child instanceof Element && ! $child->getOption('render'))) {
                continue;
            }
            $items[] = $child instanceof Element ? $child = $child->toHTML() : $child;
        }

        return implode("\n", $items);
    }

    /**
     * Render the child to HTML.
     *
     * @return string
     */
    protected function render(): string
    {
        return $this->open() . "\n" . $this->getContent() . "\n" . $this->close();
    }
}
