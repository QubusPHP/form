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

use Qubus\Form\FormBuilder\Group;

use function base_convert;
use function strtolower;
use function uniqid;

/**
 * Representation of an HTML <form>.
 *
 * @option method  Form method attribute
 * @option action  Form action attribute
 */
class Form extends Group
{
    /** @var string */
    public const TAGNAME = 'form';

    /**
     * @param array $options Element options
     * @param array $attr    HTML attributes
     */
    public function __construct(array $options = [], array $attr = [])
    {
        if (isset($options['method'])) {
            $attr['method'] = $options['method'];
        }
        $attr += ['method' => 'post'];

        if (isset($options['action'])) {
            $attr['action'] = $options['action'];
        }

        unset($options['method'], $options['action']);
        parent::__construct($options, $attr);
    }

    /**
     * Get unique identifier.
     */
    public function getId(): string
    {
        if (! isset($this->options['id'])) {
            $this->options['id'] = isset($this->options['name']) ?
            $this->options['name'] . '-form' :
            base_convert(uniqid(), 16, 36);
        }

        return $this->options['id'];
    }

    /**
     * Check if method matches and apply $_POST or $_GET parameters.
     *
     * @param bool $apply Set values using $_POST or $_GET parameters
     * @return bool
     */
    public function isSubmitted(bool $apply = true): bool
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if ($method !== strtolower($this->getAttr('method'))) {
            return false;
        }

        if ($apply) {
            $this->setValues($method === 'GET' ? $_GET : $_POST + $_FILES);
        }
        return true;
    }
}
