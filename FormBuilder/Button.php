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
 * Representation of an <button> element in a form.
 *
 * @option string content      Content displayed within the button
 * @option string escape       HTML entity encode content (default is true)
 * @option string description  Description as displayed on the label
 */
class Button extends Action
{
    /** @var string */
    public const TAGNAME = 'button';
}
