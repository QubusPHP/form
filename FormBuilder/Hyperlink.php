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
 * Base class for a link or button.
 *
 * @option string url          The href attribute
 * @option string content      Content displayed within the button
 * @option string escape       HTML entity encode content (default is true)
 * @option string description  Description as displayed on the label
 */
class Hyperlink extends Action
{
    /** @var string */
    public const TAGNAME = 'a';

    /**
     * @param array $options  Element options
     * @param array $attr     HTML attributes
     */
    public function __construct(array $options = [], array $attr = [])
    {
        if (isset($options['url'])) {
            $attr['href'] = $options['url'];
        }

        unset($options['url']);
        parent::__construct($options, $attr);
    }

    /**
     * Set the URL of the link
     *
     * @param string $url
     * @return Hyperlink $this
     */
    public function setUrl(string $url): static
    {
        $this->attr['href'] = $url;
        return $this;
    }

    /**
     * Get the URL of the link
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->attr['href'];
    }
}
