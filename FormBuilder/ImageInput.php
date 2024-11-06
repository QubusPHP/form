<?php

declare(strict_types=1);

namespace Qubus\Form\FormBuilder;

use function base64_encode;
use function chunk_split;
use function file_get_contents;
use function getimagesize;
use function htmlentities;
use function implode;
use function is_array;

class ImageInput extends FileInput
{
    /**
     * Create base64 encoded image to embed in HTML
     *
     * @param string $file
     * @return string
     */
    protected function createInlineImage(string $file): string
    {
        $picture = file_get_contents($file);
        $size = getimagesize($file);

        // base64 encode the binary data, then break it into chunks according to RFC 2045 semantics
        $base64 = chunk_split(base64_encode($picture));
        return '<img src="data:' . $size['mime'] . ';base64,' . "\n" . $base64 . '" ' . $size[3] . ' />';
    }

    /**
     * Render the widget as HTML
     *
     * @return string
     */
    public function renderElement(): string
    {
        $hidden = null;
        $name = htmlentities($this->getName());
        $attr_html = $this->attr->render(['name' => null]);

        if (is_array($this->value) && !$this->value['error']) {
            $hidden = '<input type="hidden" name="' . $name . '" '
            . 'value="^;' . htmlentities(implode(';', $this->value)) . '">' . "\n";
        }

        $preview = $this->renderPreview();
        $buttonSelect = $this->renderSelectButton();
        $buttonRemove = $this->renderRemoveButton();

        $html = <<<HTML
<div {$attr_html} data-provides="fileinput">
  $preview
  <div>
    $buttonSelect
    $buttonRemove
  </div>
</div>
HTML;

        return $html;
    }

    /**
     * Render image preview
     *
     * @return string $html
     */
    protected function renderPreview(): string
    {
        $holder = $this->getOption('holder');

        if (is_array($this->value)) {
            $image = $this->value['error'] ? null : $this->createInlineImage($this->value['tmp_name']);
        } else {
            $image = '<img src="' . htmlentities($this->value) . '">';
        }

        if ($holder) {
            $html = '<div class="fileinput-new thumbnail" data-trigger="fileinput" >' . $holder . '</div>' . "\n"
            . '<div class="fileinput-exists fileinput-preview thumbnail">' . $image . '</div>';
        } else {
            $html = '<div class="fileinput-preview thumbnail" data-trigger="fileinput" >' . $image . '</div>';
        }

        return $html;
    }
}
