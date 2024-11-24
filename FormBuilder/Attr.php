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

use ArrayIterator;
use Closure;
use DateTime;
use JsonSerializable;
use Qubus\Exception\Exception;
use stdClass;

use function array_merge;
use function array_unique;
use function htmlentities;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function json_encode;
use function method_exists;
use function preg_split;
use function sprintf;

/**
 * HTML attributes
 */
class Attr extends ArrayIterator
{
    /**
     * @param array $array
     */
    public function __construct($array = [])
    {
        $array += ['class' => []];
        if (is_string($array['class'])) {
            $array['class'] = preg_split('/\s+/', $array['class']);
        }

        parent::__construct($array);
    }

    /**
     * Cast the value of an attribute to a string.
     *
     * @param string $key
     * @param mixed $value
     * @return bool|string|null
     */
    protected function cast(string $key, mixed $value): bool|string|null
    {
        if ($key === 'class' && is_array($value)) {
            return $this->castClass($value);
        }

        if ($value instanceof Control) {
            $value = $value->getValue();
        }
        if ($value instanceof Closure) {
            $value = $value();
        }

        if ($value instanceof DateTime) {
            return $value->format('c');
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }
        if (is_array($value) || $value instanceof stdClass || $value instanceof JsonSerializable) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Cast the value of an attribute to a string.
     *
     * @param array $values
     * @return string|null
     */
    protected function castClass(array $values): ?string
    {
        $classes = [];
        foreach ($values as $value) {
            $class = $this->cast('', $value);
            if ($class) {
                $classes[] = $class;
            }
        }

        return ! empty($classes) ? implode(' ', array_unique($classes)) : null;
    }

    /**
     * Set HTML attribute(s).
     *
     * @param array|string $attr   Attribute name or assoc array with attributes
     * @param mixed|null $value
     * @return Attr $this
     */
    public function set(array|string $attr, mixed $value = null): static
    {
        $attrs = is_string($attr) ? [$attr => $value] : $attr;
        foreach ($attrs as $key => $value) {
            if (isset($value)) {
                $this[$key] = $value;
            } elseif ($this[$key]) {
                unset($this[$key]);
            }
        }

        return $this;
    }

    /**
     * Get an HTML attribute(s).
     * All attributes will be cased to their string representation.
     *
     * @param string|null $attr  Attribute name, omit to get all attributes
     * @return string|array|null
     */
    final public function get(?string $attr = null): string|array|null
    {
        return isset($attr) ? $this[$attr] : $this->getAll();
    }

    /**
     * Get an HTML attribute(s) without casting them.
     *
     * @param string|null $attr  Attribute name, omit to get all attributes
     * @return mixed
     */
    final public function getRaw(?string $attr = null): mixed
    {
        if (isset($attr)) {
            return parent::offsetGet($attr);
        }
        return $this->getAll(true);
    }

    /**
     * Get all HTML attributes.
     *
     * @param bool $raw   Don't cast attributes
     * @return array
     */
    protected function getAll(bool $raw = false): array
    {
        $attrs = $this->getArrayCopy();
        if ($raw) {
            return $attrs;
        }

        foreach ($attrs as $key => &$value) {
            $value = $this->cast($key, $value);
        }

        return $attrs;
    }

    /**
     * Check if class is present
     *
     * @param string $class
     * @return bool
     */
    public function hasClass(string $class): bool
    {
        $attr = parent::offsetGet('class');

        foreach ($attr as $cur) {
            if ($this->cast('', $cur) === $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a class
     *
     * @param array|string $class  Multiple classes may be specified as array or using a space
     * @return void
     */
    public function addClass(array|string $class): void
    {
        $attr = parent::offsetGet('class');

        if (is_string($class)) {
            $class = preg_split('/\s+/', $class);
        }
        if (! is_array($class)) {
            $class = [$class];
        }

        parent::offsetSet('class', array_merge($attr, $class));
    }

    /**
     * Remove a class
     *
     * @param array|string $class  Multiple classes may be specified as array or using a space
     * @return Attr $this
     */
    public function removeClass(array|string $class): static
    {
        $attr = parent::offsetGet('class');
        if (! is_array($class)) {
            $remove = preg_split('/\s+/', $class);
        }

        foreach ($attr as $i => $cur) {
            if (in_array($this->cast('', $cur), $remove)) {
                unset($attr[$i]);
            }
        }

        parent::offsetSet('class', $attr);
        return $this;
    }

    /**
     * Get attributes as string
     *
     * @param array $override  Attributes to add or override
     * @return string
     */
    public function render(array $override = []): string
    {
        foreach ($override as $key => &$value) {
            $value = $this->cast($key, $value);
        }

        $attrs = array_merge($override, $this->getAll());

        $pairs = [];
        foreach ($attrs as $key => $value) {
            static::appendAttr($pairs, $key, $value);
        }

        return implode(' ', $pairs);
    }

    /**
     * Get specific attributes as string
     *
     * @param array|string $attrs
     * @return string
     */
    public function renderOnly(array|string $attrs): string
    {
        $pairs = [];
        foreach ((array) $attrs as $key) {
            static::appendAttr($pairs, $key, $this[$key]);
        }

        return implode(' ', $pairs);
    }

    /**
     * Get attributes as string
     *
     * @return string
     */
    final public function __toString()
    {
        return $this->render();
    }

    /**
     * Get value for an offset
     *
     * @param string $index
     * @return string|null
     */
    public function offsetGet(mixed $index): ?string
    {
        if (! parent::offsetExists($index)) {
            return null;
        }

        $value = parent::offsetGet($index);
        return $this->cast($index, $value);
    }

    /**
     * Unset value at offset
     *
     * @param mixed $index
     */
    public function offsetUnset(mixed $index): void
    {
        parent::offsetExists($index) && parent::offsetUnset($index);
    }

    /**
     * Set value for an offset
     *
     * @param mixed $index   The index to set for.
     * @param mixed $newval  The new value to store at the index.
     */
    public function offsetSet(mixed $index, mixed $newval): void
    {
        if ($index === 'class' && is_string($newval)) {
            $newval = preg_split('/\s+/', $newval);
        }
        parent::offsetSet($index, $newval);
    }

    /**
     * Append a value.
     *
     * @param mixed $value
     * @throws Exception
     */
    final public function append(mixed $value): void
    {
        throw new Exception(
            sprintf(
                'Unable to add value `%s`. You need to use associated keys.',
                $value
            )
        );
    }

    /**
     * Add a key/value as HTML attribute.
     *
     * @param array $pairs
     * @param string $key
     * @param mixed  $value  Scalar
     */
    protected static function appendAttr(array &$pairs, string $key, mixed $value): void
    {
        if (! isset($value) || $value === false) {
            return;
        }

        $set = $value === true ? null : '="' . htmlentities($value) . '"';
        $pairs[] = htmlentities($key) . $set;
    }
}
