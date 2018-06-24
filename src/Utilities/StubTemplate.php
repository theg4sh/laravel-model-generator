<?php

namespace Iber\Generator\Utilities;

use Iber\Generator\Utilities\VariableConversion;

class StubTemplate
{
    private $template;
    private $placeholders;

    public function __construct($template)
    {
        $this->template = $template;
        preg_match_all('/{{[ \t]*([^ \t}]+)[ \t]*}}/', $template, $placeholders);
        for ($i=0; $i<sizeof($placeholders[0]); $i++) {
            $this->placeholders[$placeholders[1][$i]] = [$placeholders[0][$i], ''];
        }
    }

    protected function formatValue($value)
    {
        if (is_array($value)) {
            return VariableConversion::convertArrayToString($value);
        } else if (is_bool($value)) {
            return VariableConversion::convertBooleanToString($value);
        } else if (is_string($value)) {
            return "'" . $value . "'";
        } else {
            return $value;
        }
    }

    public function bind($placeholder, $value)
    {
        if (array_key_exists($placeholder, $this->placeholders)) {
            $this->placeholders[$placeholder][1] = $value;
        }
    }

    public function bindFormat($placeholder, $value)
    {
        $this->bind($placeholder, $this->formatValue($value));
    }

    public function bindProperty($placeholder, $access, $property, $value)
    {
        $this->bind($placeholder, $access . ' $' . $property . ' = ' . $this->formatValue($value) . ";");
    }

    public function finalize()
    {
        $model = $this->template;
        foreach($this->placeholders as $pv) {
            $model = str_replace($pv[0], $pv[1], $model);
        }
        $model = preg_replace("/(\r?\n)([ \t]*\r?\n)+/m", '\1\1', $model);
        return $model;
    }
}
