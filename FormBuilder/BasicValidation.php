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

use function addcslashes;
use function array_filter;
use function ctype_alpha;
use function ctype_digit;
use function ctype_xdigit;
use function date_parse_from_format;
use function implode;
use function is_array;
use function is_int;
use function is_numeric;
use function json_encode;
use function method_exists;
use function preg_match;
use function preg_replace_callback;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function ucwords;

/**
 * Basic input validation
 *  - Server side equivalent of HTML5 validation.
 *  - Process upload errors.
 *  - Client side support for minlength and matching element values (using JavaScript).
 */
trait BasicValidation
{
    /**
     * Validate if the control has a value if it's required.
     *
     * @return bool
     */
    protected function validateRequired(): bool
    {
        if ($this->getOption('required')) {
            $value = $this->getValue();
            if ($value === null || $value === '') {
                $this->error = $this->setError($this->getOption('error:required'));
                return false;
            }
        }

        return true;
    }

    /**
     * Validate if min and max for value.
     *
     * @return bool
     */
    protected function validateMinMax(): bool
    {
        $value = $this->getValue();

        $min = $this->getOption('min');
        if (isset($min) && $min !== false && $value < $min) {
            $this->setError($this->getOption('error:min'));
            return false;
        }

        $max = $this->getOption('max');
        if (isset($max) && $max !== false && $value > $max) {
            $this->setError($this->getOption('error:max'));
            return false;
        }

        return true;
    }

    /**
     * Validate the length of the value.
     *
     * @return bool
     */
    protected function validateLength(): bool
    {
        $value = $this->getValue();

        $minlength = $this->getOption('minlength') ?: $this->getOption('data-minlength');
        if (isset($minlength) && $minlength !== false && strlen($value) > $minlength) {
            $this->setError($this->getOption('error:minlength'));
            return false;
        }

        $maxlength = $this->getOption('maxlength');
        if (isset($maxlength) && $maxlength !== false && strlen($value) > $maxlength) {
            $this->setError($this->getOption('error:maxlength'));
            return false;
        }

        return true;
    }

    /**
     * Validate the value of the control against a regex pattern.
     *
     * @return bool
     */
    protected function validatePattern(): bool
    {
        $pattern = $this->getOption('pattern');
        if ($pattern && ! preg_match('/' . str_replace('/', '\/', $pattern) . '/A', $this->getValue())) {
            $this->setError($this->getOption('error:pattern'));
            return false;
        }

        return true;
    }

    /**
     * Match value against another control.
     *
     * @return bool
     */
    protected function validateMatch(): bool
    {
        $other = $this->getOption('match');
        if (! isset($other)) {
            return true;
        }

        if (! $other instanceof Control) {
            $other = $this->getForm()->getElement($other);
        }

        if ($this->getValue() !== $other->getValue()) {
            $this->setError($this->getOption('error:match'));
            return false;
        }

        return true;
    }

    /**
     * Check if there were upload errors.
     *
     * @return bool
     */
    protected function validateUpload(): bool
    {
        $value = $this->getValue();

        // No error
        if (! is_array($value) || empty($value['error'])) {
            return true;
        }

        // An error
        $errors = $this->getOption('error:upload');
        $this->setError($errors[$value['error']]);
        return false;
    }

    /**
     * Validate if value matches the input type.
     *
     * @return bool
     */
    protected function validateType(): bool
    {
        $type = $this->attr['type'];
        $method = 'validateType' . str_replace(' ', '', ucwords(str_replace('-', ' ', $type)));

        if (! method_exists($this, $method) || $this->$method()) {
            return true;
        }

        if ($type !== 'file') {
            $this->setError($this->getOption('error:type'));
        }
        return false;
    }

    /**
     * Validate the value for 'color' input type.
     *
     * @return bool
     */
    protected function validateTypeColor(): bool
    {
        $value = $this->getValue();
        return strlen($value) === 7 && $value[0] === '#' && ctype_xdigit(substr($value, 1));
    }

    /**
     * Validate the value for 'number' input type.
     *
     * @return bool
     */
    protected function validateTypeNumber(): bool
    {
        $value = $this->getValue();
        return is_int($value) || ctype_digit((string) $value);
    }

    /**
     * Validate the value for 'range' input type.
     *
     * @return bool
     */
    protected function validateTypeRange(): bool
    {
        return is_numeric($this->getValue());
    }

    /**
     * Validate the value for 'date' input type.
     *
     * @return bool
     */
    protected function validateTypeDate(): bool
    {
        $res = date_parse_from_format("Y-m-d", $this->getValue());
        return $res['error_count'] === 0;
    }

    /**
     * Validate the value for 'datetime' input type.
     *
     * @return bool
     */
    protected function validateTypeDatetime(): bool
    {
        $res = date_parse_from_format("Y-m-d\TH:i:s", $this->getValue());
        return $res['error_count'] === 0;
    }

    /**
     * Validate the value for 'datetime' input type.
     *
     * @return bool
     */
    protected function validateTypeDatetimeLocal(): bool
    {
        $res = date_parse_from_format(DateTime::RFC3339, $this->getValue());
        return $res['error_count'] === 0;
    }

    /**
     * Validate the value for 'datetime' input type.
     *
     * @return bool
     */
    protected function validateTypeTime(): bool
    {
        $res = date_parse_from_format("H:i:s", $this->getValue());
        return $res['error_count'] === 0;
    }

    /**
     * Validate the value for 'month' input type.
     *
     * @return bool
     */
    protected function validateTypeMonth(): bool
    {
        $res = date_parse_from_format("Y-m", $this->getValue());
        return $res['error_count'] === 0;
    }

    /**
     * Validate the value for 'week' input type.
     *
     * @return bool
     */
    protected function validateTypeWeek(): bool
    {
        $res = date_parse_from_format("o-\WW", $this->getValue());
        return $res['error_count'] === 0;
    }

    /**
     * Validate the value for 'url' input type.
     *
     * @return bool
     */
    protected function validateTypeUrl(): bool
    {
        $value = $this->getValue();
        $pos = strpos($value, ':');
        return $pos !== false && ctype_alpha(substr($value, 0, $pos));
    }

    /**
     * Validate the value for 'email' input type.
     *
     * @return bool
     */
    protected function validateTypeEmail(): bool
    {
        return preg_match('/^[\w\-\.]+@[\w\-\.]+\w+$/', $this->getValue());
    }

    /**
     * Get JavaScript for custom validation.
     *
     * @return string
     */
    public function getValidationScript(): string
    {
        if (! $this->getOption('validation-script')) {
            return '';
        }

        $rules = $this->getValidationScriptRules();
        if (empty($rules)) {
            return '';
        }

        foreach ($this->getDecorators() as $decorator) {
            if ($decorator->applyToValidationScript($this, $rules)) {
            }
        }

        return $this->generateValidationScript($rules);
    }

    /**
     * Get the rules to build up the validation script
     *
     * @return array
     */
    protected function getValidationScriptRules(): array
    {
        $rules['minlength'] = $this->getValidationScriptMinlength();
        $rules['match'] = $this->getValidationScriptMatch();

        return array_filter($rules);
    }

    /**
     * Generate validation script
     *
     * @param array $rules
     * @return string
     */
    protected function generateValidationScript(array $rules): string
    {
        $id = addcslashes($this->getId(), '"');

        foreach ($rules as $test => &$rule) {
            $message = $this->parseForScript($this->getOption('error:' . $test));

            $rule = <<<SCRIPT
if (!$rule) { 
    this.setCustomValidity("$message");
    return;
} else {
    this.setCustomValidity("");
}
SCRIPT;
        }

        $script = implode("\n", $rules);

        return <<<SCRIPT
<script type="text/javascript">
    document.getElementById("$id").addEventListener("input", function() {
        $script
    });
</script>
SCRIPT;
    }

    /**
     * Get script to match the minimum length
     *
     * @return string|null
     */
    protected function getValidationScriptMinlength(): ?string
    {
        $minlength = $this->getOption('minlength');
        if (! isset($minlength)) {
            return null;
        }

        return 'this.value.length >= ' . $minlength;
    }

    /**
     * Get script to match other element
     *
     * @return string|null
     */
    protected function getValidationScriptMatch(): ?string
    {
        $other = $this->getOption('match');
        if (! $other) {
            return null;
        }

        if (! $other instanceof Control) {
            $other = $this->getForm()->getElement($other);
        }

        return "this.value == " . $this->castForScript($other);
    }

    /**
     * Parse a message, inserting values for placeholders for JavaScript.
     *
     * @param string $message
     * @return string
     */
    public function parseForScript(string $message): string
    {
        return preg_replace_callback('/{{\s*([^}])++\s*}}/', [$this, 'resolvePlaceholderForScript'], $message);
    }

    /**
     * Get a value for a placeholder for JavaScript.
     *
     * @param string $var
     * @return string
     */
    protected function resolvePlaceholderForScript($var): string
    {
        // preg_replace callback
        if (is_array($var)) {
            $var = $var[1];
        }

        if ($this->getOption($var) !== null) {
            return '" + this.getOptionibute("' . addcslashes($var, '"') . '") + "';
        }

        switch ($var) {
            case 'value':
                return '" + this.value + "';
            case 'length':
                return '" + this.value.length + "';
        }

        $value = $this->resolvePlaceholder($var);

        if ($value instanceof Control) {
            $id = addcslashes($value->getId(), '"');
            return '" + document.getElementById("' . $id . '").value + "';
        }

        return json_encode($value);
    }
}
