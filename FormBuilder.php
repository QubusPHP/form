<?php

/**
 * Qubus\Form
 *
 * @link       https://github.com/QubusPHP/form
 * @copyright  2023 Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Form;

use Qubus\Exception\Data\TypeException;
use Qubus\Form\FormBuilder\Button;
use Qubus\Form\FormBuilder\ChoiceList;
use Qubus\Form\FormBuilder\Control;
use Qubus\Form\FormBuilder\Decorator;
use Qubus\Form\FormBuilder\Decorator\Dindent;
use Qubus\Form\FormBuilder\Decorator\SimpleFilter;
use Qubus\Form\FormBuilder\Decorator\SimpleValidation;
use Qubus\Form\FormBuilder\Decorator\Tidy;
use Qubus\Form\FormBuilder\Div;
use Qubus\Form\FormBuilder\Element;
use Qubus\Form\FormBuilder\Fieldset;
use Qubus\Form\FormBuilder\Group;
use Qubus\Form\FormBuilder\Hyperlink;
use Qubus\Form\FormBuilder\Input;
use Qubus\Form\FormBuilder\Label;
use Qubus\Form\FormBuilder\Legend;
use Qubus\Form\FormBuilder\Select;
use Qubus\Form\FormBuilder\Span;
use Qubus\Form\FormBuilder\Textarea;
use ReflectionClass;
use ReflectionException;

use function array_slice;
use function func_get_args;
use function sprintf;

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_PARTIAL;

/**
 * FormBuilder factory
 */
class FormBuilder
{
    /**
     * Default options.
     *
     * @var array
     */
    public static array $options = [
        'render'            => true, // Render element
        'validate'          => true, // Server-side validation
        'validation-script' => true, // Include <script> for validation that isn't supported by HTML5
        'add-hidden'        => true, // Add hidden input for checkbox inputs
        'required-suffix'   => ' *', // Suffix label for required controls
        'container'         => 'div', // Place each form element in a container
        'label'             => true, // Add a label for each form element
        'error:required'    => "Please fill out this field",
        'error:type'        => "Please enter a {{type}}",
        'error:min'         => "Value must be greater or equal to {{min}}",
        'error:max'         => "Value must be less or equal to {{max}}",
        'error:minlength'   => "Please use {{minlength}} characters or more for this text",
        'error:maxlength'   => "Please shorten this text to {{maxlength}} characters or less",
        'error:pattern'     => "Please match the requested format",
        'error:same'        => "Please match the value of {{other}}",
        'error:upload'      => [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE
                => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
            UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
        ],
    ];

    /**
     * Element types.
     *
     * @var array
     */
    public static array $elements = [
        'div'      => [Div::class, ['tagname' => 'div']],
        'form'     => [Form::class],
        'fieldset' => [Fieldset::class],
        'group'    => [Group::class],
        'span'     => [Span::class],
        'label'    => [Label::class],
        'legend'   => [Legend::class],
        'button'   => [Button::class],
        'link'     => [Hyperlink::class],
        'choice'   => [ChoiceList::class],
        'multi'    => [ChoiceList::class, ['multiple' => true]],
        'input'    => [Input::class],
        'select'   => [Select::class],
        'textarea' => [Textarea::class],
        'boolean'  => [Input::class, ['type' => 'checkbox']],
        'text'     => [Input::class, ['type' => 'text']],
        'hidden'   => [Input::class, ['type' => 'hidden']],
        'file'     => [Input::class, ['type' => 'file']],
        'color'    => [Input::class, ['type' => 'color']],
        'number'   => [Input::class, ['type' => 'number']],
        'decimal'  => [Input::class, ['type' => 'text'], ['pattern' => '-?\d+(\.\d+)?']],
        'range'    => [Input::class, ['type' => 'range']],
        'date'     => [Input::class, ['type' => 'date']],
        'datetime' => [Input::class, ['type' => 'datetime-local']],
        'time'     => [Input::class, ['type' => 'time']],
        'month'    => [Input::class, ['type' => 'month']],
        'week'     => [Input::class, ['type' => 'week']],
        'url'      => [Input::class, ['type' => 'url']],
        'email'    => [Input::class, ['type' => 'email']],
    ];

    /**
     * Decorator types.
     *
     * @var array
     */
    public static array $decorators = [
        'filter'     => SimpleFilter::class,
        'validation' => SimpleValidation::class,
        'tidy'       => Tidy::class,
        'indent'     => Dindent::class,
    ];

    /**
     * Create a form element.
     *
     * @param string $type Element type
     * @param array $options Element options
     * @param array $attr HTML attributes
     * @return Element|Control
     * @throws TypeException
     */
    public static function element(string $type, array $options = [], array $attr = []): Element|Control
    {
        if (! isset(static::$elements[$type])) {
            throw new TypeException(
                sprintf(
                    'Unknown element type `%s`.',
                    $type
                )
            );
        }

        $class = static::$elements[$type][0];
        if (isset(static::$elements[$type][1])) {
            $options += static::$elements[$type][1];
        }
        if (isset(static::$elements[$type][2])) {
            $attr += static::$elements[$type][2];
        }

        return new $class($options, $attr);
    }

    /**
     * Create a form decorator.
     *
     * @param string $type Decorator type
     * @param mixed $_ Additional arguments are passed to the constructor.
     * @return Decorator
     * @throws TypeException
     * @throws ReflectionException
     */
    public static function decorator($type): Decorator
    {
        if (! isset(static::$decorators[$type])) {
            throw new TypeException(sprintf('Unknown decorator `%s`.', $type));
        }

        $class = static::$decorators[$type];
        $args = array_slice(func_get_args(), 1);

        $refl = new ReflectionClass($class);
        return $refl->newInstanceArgs($args);
    }
}
