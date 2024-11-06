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

namespace Qubus\Form;

abstract class FormValidator
{
    public static array $rules_save = [];

    public static array $rules_update = [];

    public function __construct()
    {
    }

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }
}
