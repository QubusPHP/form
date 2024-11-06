<?php

declare(strict_types=1);

namespace Qubus\Form\FormBuilder;

use Qubus\Exception\Data\TypeException;

use function basename;
use function dirname;
use function file_exists;
use function glob;
use function htmlentities;
use function ini_get;
use function is_dir;
use function is_string;
use function is_uploaded_file;
use function mkdir;
use function move_uploaded_file;
use function pathinfo;
use function sprintf;
use function substr;
use function trigger_error;

use const E_USER_WARNING;
use const GLOB_BRACE;
use const PATHINFO_EXTENSION;

class FileInput extends Control
{
    public static array $buttons = array(
        'select' => "Select file",
        'change' => "Change",
        'remove' => "Remove"
    );

    /**
     * @var null|string
     */
    protected mixed $value = null;


    /**
     * Class constructor.
     *
     * @param array|null $name
     * @param array|null $description Description as displayed on the label
     * @param array $attrs HTML attributes
     * @param array $options Control options
     * @throws TypeException
     */
    public function __construct(array $name = null, array $description = null, array $attrs = [], array $options = [])
    {
        if (!isset($options['buttons'])) {
            $options['buttons'] = self::$buttons;
        }

        $options['name'] = $name ?? '';
        $options['description'] = $description ?? '';

        parent::__construct($options, $attrs);
        $this->addClass(['fileinput', 'fileinput-new']);
    }


    /**
     * Get the value of the control.
     *
     * @return string
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set the value of the control.
     *
     * @param string $value
     * @return FileInput $this
     */
    public function setValue($value): static
    {
        if (is_string($value) && substr($value, 0, 2) == '^;') {
            [, $name, $type, $size, $tmpName, $error] = explode(':', $value);
            $tmpName = ini_get('upload_tmp_dir') . '/' . $tmpName;
            $value = null;

            if (is_uploaded_file($tmpName)) {
                $value = [
                    'name' => $name,
                    'type' => $type,
                    'size' => $size,
                    'tmp_name' => $tmpName,
                    'error' => $error
                ];
            } else {
                trigger_error(sprintf("'%s' is not an uploaded file", $tmpName), E_USER_WARNING);
            }
        }

        if (is_array($value) && $value['error'] === UPLOAD_ERR_NO_FILE) {
            return $this;
        }

        $this->value = $value;

        if (!$this->value || (is_array($value) && $value['error'])) {
            $this->removeClass('fileinput-exists')->addClass('fileinput-new');
        } else {
            $this->removeClass('fileinput-new')->addClass('fileinput-exists');
        }

        return $this;
    }

    /**
     * Check if a new file is uploaded.
     *
     * @return bool
     */
    public function isUploaded(): bool
    {
        return is_array($this->value);
    }

    /**
     * Check if the file is cleared.
     *
     * @return bool
     */
    public function isCleared(): bool
    {
        return $this->value === '';
    }

    /**
     * Set the name of the element.
     *
     * @param string $name
     * @return Control $this
     */
    public function setName(string $name): Control
    {
        if ($this->getAttr('multiple') && substr($name, -2) != '[]') {
            $name .= '[]';
        }

        return $this->setAttr('name', $name);
    }

    /**
     * Move (or clear) uploaded file.
     *
     * @param string $destination File name, glob expression or directory name
     * @return bool|string Path to uploaded file
     */
    public function moveUploadedFile(string $destination): bool|string
    {
        if (!$this->isUploaded() && !$this->isCleared()) {
            return false;
        }

        foreach (glob($destination, GLOB_BRACE) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if ($this->isCleared()) {
            return false;
        }

        if (is_dir($destination)) {
            $destination .= basename($this->value['name']);
        } else {
            $parts = pathinfo($destination);
            $ext = pathinfo($this->value['name'], PATHINFO_EXTENSION);
            $destination = $parts['dirname'] . '/' . $parts['filename'] . '.' . $ext;
        }

        if (!file_exists(dirname($destination))) {
            mkdir(dirname($destination), 0775, true);
        }

        if (!move_uploaded_file($this->value['tmp_name'], $destination)) {
            return false;
        }

        return $destination;
    }

    /**
     * Validate the select control.
     *
     * @return boolean
     */
    public function validate(): bool
    {
        if (!$this->getOption('basic-validation')) {
            return true;
        }

        return
        $this->validateRequired() &&
        $this->validateUpload();
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

        if (is_array($this->value) && !$this->value['error']) {
            $hidden = '<input type="hidden" name="' . $name . '" '
            . 'value="^;' . htmlentities(join(';', $this->value)) . '">' . "\n";
        }

        $attr_html = $this->attr->render(['name' => null, 'multiple' => null]);

        $preview = $this->renderPreview();
        $buttonSelect = $this->renderSelectButton();
        $buttonRemove = $this->renderRemoveButton();

        $html = <<<HTML
<div {$attr_html} data-provides="fileinput">
  {$hidden}<div class="input-append">
    <div class="uneditable-input span3">{$preview}</div>{$buttonSelect}{$buttonRemove}
  </div>
</div>
HTML;

        return $html;
    }

    /**
     * Render the preview for existing files.
     *
     * @return string
     */
    protected function renderPreview(): string
    {
        if (is_array($this->value)) {
            $value = $this->value['error'] ? null : htmlentities(basename($this->value['name']));
        } else {
            $value = htmlentities(basename($this->value));
        }

        return <<<HTML
<i class="icon-file fileinput-exists"></i> <span class="fileinput-preview">$value</span>
HTML;
    }

    /**
     * Render the select button
     *
     * @return string
     */
    protected function renderSelectButton(): string
    {
        $attr = $this->attr->renderOnly(['name', 'multiple']);

        $buttonSelect = htmlentities($this->getOption('select-button') ?: 'Select');
        $buttonChange = htmlentities($this->getOption('change-button') ?: 'Change');

        return <<<HTML
<span class="btn btn-default btn-file"><span class="fileinput-new">$buttonSelect</span><span class="fileinput-exists">$buttonChange</span><input type="file" $attr /></span> 
HTML;
    }

    /**
     * Render the remove button
     *
     * @return string|null
     */
    protected function renderRemoveButton(): ?string
    {
        $buttonRemove = htmlentities($this->getOption('remove-button') ?: 'Remove');
        if (!$buttonRemove) {
            return null;
        }

        return <<<HTML
<button class="btn btn-default fileinput-exists" data-dismiss="fileinput">$buttonRemove</button>
HTML;
    }
}
