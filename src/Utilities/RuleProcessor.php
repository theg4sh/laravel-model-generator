<?php

namespace Iber\Generator\Utilities;

use Iber\Generator\Exceptions\InvalidRuleException;

/**
 * Class RuleProcessor.
 */
class RuleProcessor
{
    private $_fillable;
    private $_guarded;
    private $_timestamps;

    public function __construct($fillable, $guarded, $timestamps)
    {
        $this->_fillable = $fillable;
        $this->_guarded = $guarded;
        $this->_timestamps = $timestamps;
    }

    /**
     * Check if the value matches the rules.
     *
     * @param $rules
     * @param $value
     *
     * @return bool
     *
     * @throws InvalidRuleException
     */
    protected function check($rules, $value)
    {
        if(empty($rules)) {
            return true;
        }

        $value = strtolower($value);

        $rules = $this->parseRules($rules);

        foreach ($rules as $rule => $options) {
            if (method_exists($this, $rule)) {
                $passed = $this->{$rule}($options, $value);

                if ($passed) {
                    return true;
                }
            } else {
                throw new InvalidRuleException('Rule '.$rule.' not implemented');
            }
        }

        return false;
    }

    public function checkFillable($value)
    {
        return $this->check($this->_fillable, $value);
    }
    public function checkGuarded($value)
    {
        return $this->check($this->_guarded, $value);
    }
    public function checkTimestamps($value)
    {
        return $this->check($this->_timestamps, $value);
    }

    /**
     * Parse rules.
     *
     * @param $rules
     *
     * @return array
     */
    public function parseRules($rules)
    {
        $groups = [];
        if (!empty($rules)) {
            $rules = str_replace(' ', '', $rules);
            $split = explode(',', $rules); //'ends:_id|ids,equals:id'
            foreach ($split as $rule) {
                list($type, $filters) = explode(':', $rule);
                $groups[$type] = explode('|', $filters);
            }
        }

        return $groups;
    }

    /**
     * Check if a value starts in any of the given values.
     *
     * @param $options
     * @param $value
     *
     * @return bool
     */
    public function starts($options, $value)
    {
        foreach ($options as $option) {
            $passed = (strrpos($value, $option, -strlen($value)) !== false);

            if ($passed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a value ends in any of the given values.
     *
     * @param $options
     * @param $value
     *
     * @return bool
     */
    public function ends($options, $value)
    {
        foreach ($options as $option) {
            $passed = (substr($value, -strlen($option)) === $option);

            if ($passed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a value is equal to any of the given values.
     *
     * @param $options
     * @param $value
     *
     * @return bool
     */
    public function equals($options, $value)
    {
        foreach ($options as $option) {
            $passed = ($value === $option);

            if ($passed) {
                return true;
            }
        }

        return false;
    }
}
