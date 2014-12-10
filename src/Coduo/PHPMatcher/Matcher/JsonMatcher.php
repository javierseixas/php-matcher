<?php

namespace Coduo\PHPMatcher\Matcher;

class JsonMatcher extends Matcher
{
    const TRANSFORM_QUOTATION_PATTERN = '/([^"])@([a-zA-Z0-9\.]+)@([^"])/';
    const TRANSFORM_QUOTATION_REPLACEMENT = '$1"@$2@"$3';
    const IGNORE_EXTRA_KEYS_PATTERN = "@ignore_extra_keys@";

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
        $nonStrictMatching = preg_match(self::IGNORE_EXTRA_KEYS_PATTERN, $pattern);

        $pattern = str_replace(self::IGNORE_EXTRA_KEYS_PATTERN, "", $pattern);

        if (!is_string($value) || !$this->isValidJson($value)) {
            return false;
        }

        $pattern = $this->transformPattern($pattern);
        $match = $this->doMatch(json_decode($value, true), json_decode($pattern, true), $nonStrictMatching);
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
     * @param $value
     * @param $pattern
     * @param $matches
     * @return bool
     */
    private function doMatch($value, $pattern, $nonStrictMatching)
    {
        if ($nonStrictMatching) {
            return $this->nonStrictMatcher->match($value, $pattern);
        }

        return $this->matcher->match($value, $pattern);
    }

}
