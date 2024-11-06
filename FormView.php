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

abstract class FormView extends Form
{
    protected array $opts = [];

    protected array $attrs = [];

    public function __construct()
    {
        parent::__construct($this->opts, $this->attrs);
    }

    abstract public function buildForm(): Form;
}
