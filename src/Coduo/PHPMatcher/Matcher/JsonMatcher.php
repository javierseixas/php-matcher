<?php

namespace Coduo\PHPMatcher\Matcher;

use Coduo\PHPMatcher\Exception\Exception;

class JsonMatcher extends Matcher
{
    const TRANSFORM_QUOTATION_PATTERN = '/([^"])@([a-zA-Z0-9\.]+)@([^"])/';
    const TRANSFORM_QUOTATION_REPLACEMENT = '$1"@$2@"$3';
    const IGNORE_EXTRA_KEYS_PATTERN = "/\|([a-z_,]+)\|/";
    const STRICTNESSERS_EXTRACTION_PATTERN = "/\|([a-z_]+)(,[a-z_]+)\|*/";

    /**
     * @var
     */
    private $matcher;

    private $nonStrictMatcher;

    /**
     * @param ValueMatcher $matcher
     */
    public function __construct(ValueMatcher $matcher, ValueMatcher $nonStrictMatcher = null)
    {
        $this->matcher = $matcher;
        $this->nonStrictMatcher = $nonStrictMatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function match($value, $pattern)
    {
        $strictnessers = $this->isNonStrict($pattern);

        $pattern = $this->removeNonStrictPattern($pattern);

        if (!is_string($value) || !$this->isValidJson($value)) {
            return false;
        }

        $pattern = $this->transformPattern($pattern);
        $match = $this->doMatch(json_decode($value, true), json_decode($pattern, true), $strictnessers);
        if (!$match) {
            $this->error = $this->matcher->getError();
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function canMatch($pattern)
    {
        if (!is_string($pattern)) {
            return false;
        }

        return $this->isValidJson($this->transformPattern($pattern));
    }

    private function isValidJson($string)
    {
        @json_decode($string, true);

        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Wraps placeholders which arent wrapped with quotes yet
     *
     * @param $pattern
     * @return mixed
     */
    private function transformPattern($pattern)
    {
        return preg_replace(self::TRANSFORM_QUOTATION_PATTERN, self::TRANSFORM_QUOTATION_REPLACEMENT, $pattern);
    }

    /**
     * Choose between strict or non strict matching
     *
     * @param $value
     * @param $pattern
     * @param $matches
     * @return bool
     */
    private function doMatch($value, $pattern, $strictnessers)
    {
        if (count($strictnessers) > 0) {
            if (is_null($this->nonStrictMatcher)) {
                throw new Exception(sprintf("There is not any NonStrictMatcher available. Please include it during the constuction of the class %s", __FILE__));
            }

            foreach ($strictnessers as $strictnesser) {
                switch ($strictnesser) {
                    case 'case_insensitive':
                        array_walk_recursive($value, function(&$item, $key) {
                            $item = strtolower($item);
                        });
                        break;
                    default:

                }
            }

            if (in_array('ignore_extra_keys', $strictnessers)) {
                return $this->nonStrictMatcher->match($value, $pattern);
            }
        }

        return $this->matcher->match($value, $pattern);
    }

    /**
     * @param $pattern
     * @return int
     */
    protected function isNonStrict($pattern)
    {
        preg_match(self::IGNORE_EXTRA_KEYS_PATTERN, $pattern, $matches);

        return count($matches) > 1 ? explode(",", $matches[1]) : array();
    }

    /**
     * @param $pattern
     * @return string
     */
    protected function removeNonStrictPattern($pattern)
    {
        return preg_replace(self::IGNORE_EXTRA_KEYS_PATTERN, "", $pattern);
    }

}
