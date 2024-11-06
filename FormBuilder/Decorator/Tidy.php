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

namespace Qubus\Form\FormBuilder\Decorator;

use Qubus\Form\FormBuilder\Decorator;
use Qubus\Form\FormBuilder\Element;

use function implode;
use function tidy_parse_string;

/**
 * Tidy up the HTML.
 *
 * @link http://www.php.net/tidy
 */
class Tidy extends Decorator
{
    /**
     * Tidy configuration
     *
     * @var array
     */
    protected array $config = [
        'doctype'        => 'omit',
        'output-html'    => true,
        'show-body-only' => true,
    ];

    /**
     * @param array $config Tidy configuration.
     * @param bool  $deep   Tidy each individual child.
     */
    public function __construct(array $config = [], bool $deep = false)
    {
        $this->config = $config + $this->config;
        $this->deep = $deep;
    }

    /**
     * Render to HTML.
     *
     * @param Element $element
     * @param string $html     Original rendered html
     * @return string
     */
    public function render(Element $element, string $html): string
    {
        $tidy = tidy_parse_string($html, $this->config);
        $tidy->cleanRepair();
        return implode($tidy->body()->child) . "\n";
    }
}
