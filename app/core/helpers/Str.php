<?php

namespace App\Core\Helpers;

class Str
{
    /**
     * The cache of snake-cased words
     *
     * @var array
     */
    protected static $snakeCache = [];
    
    /**
     * The cache of camel-cased words
     *
     * @var array
     */
    protected static $camelCache = [];
    
    /**
     * The cache of studly-cased words
     *
     * @var array
     */
    protected static $studlyCache = [];
    
    /**
     * Convert a string to snake case
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        $key = $value . $delimiter;
        
        if (isset(static::$snakeCache[$key])) {
            return static::$snakeCache[$key];
        }
        
        if (!ctype_lower($value)) {
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/', '$1' . $delimiter, $value));
        }
        
        return static::$snakeCache[$key] = $value;
    }
    
    /**
     * Convert a value to camel case
     *
     * @param string $value
     * @return string
     */
    public static function camel($value)
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }
        
        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }
    
    /**
     * Convert a value to studly caps case
     *
     * @param string $value
     * @return string
     */
    public static function studly($value)
    {
        $key = $value;
        
        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }
        
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        
        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }
    
    /**
     * Convert a string to kebab case
     *
     * @param string $value
     * @return string
     */
    public static function kebab($value)
    {
        return static::snake($value, '-');
    }
    
    /**
     * Determine if a given string contains a given substring
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Determine if a given string starts with a given substring
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Determine if a given string ends with a given substring
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Cap a string with a single instance of a given value
     *
     * @param string $value
     * @param string $cap
     * @return string
     */
    public static function finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');
        
        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }
    
    /**
     * Determine if a given string matches a given pattern
     *
     * @param string|array $pattern
     * @param string $value
     * @return bool
     */
    public static function is($pattern, $value)
    {
        $patterns = is_array($pattern) ? $pattern : [$pattern];
        
        if (empty($patterns)) {
            return false;
        }
        
        foreach ($patterns as $pattern) {
            // If the given value is an exact match we can of course return true right
            // from the beginning. Otherwise, we will translate asterisks and do the
            // actual pattern matching against the two strings to see if they match.
            if ($pattern == $value) {
                return true;
            }
            
            $pattern = preg_quote($pattern, '#');
            
            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings such as libraries/
            // matching any of the patterns while the file is being checked.
            $pattern = str_replace('\*', '.*', $pattern);
            
            if (preg_match('#^' . $pattern . '\z#u', $value) === 1) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate a more truly "random" alpha-numeric string
     *
     * @param int $length
     * @return string
     */
    public static function random($length = 16)
    {
        $string = '';
        
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        
        return $string;
    }
    
    /**
     * Convert a string to lowercase
     *
     * @param string $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }
    
    /**
     * Convert a string to uppercase
     *
     * @param string $value
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }
    
    /**
     * Get the plural form of an English word
     *
     * @param string $value
     * @param int $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        return static::pluralize($value, $count);
    }
    
    /**
     * Get the singular form of an English word
     *
     * @param string $value
     * @return string
     */
    public static function singular($value)
    {
        return static::singularize($value);
    }
    
    /**
     * Pluralize the given word based on the given count
     *
     * @param string $value
     * @param int $count
     * @return string
     */
    protected static function pluralize($value, $count)
    {
        // Simple pluralization rules
        if ($count === 1) {
            return $value;
        }
        
        $last = strtolower(substr($value, -1));
        $lastTwo = strtolower(substr($value, -2));
        
        // Words ending in -y (e.g., category -> categories)
        if ($last === 'y' && !in_array($lastTwo, ['ay', 'ey', 'iy', 'oy', 'uy'])) {
            return substr($value, 0, -1) . 'ies';
        }
        
        // Words ending in -s, -x, -z, -ch, -sh (e.g., box -> boxes)
        if (in_array($last, ['s', 'x', 'z']) || in_array($lastTwo, ['ch', 'sh'])) {
            return $value . 'es';
        }
        
        // Default pluralization
        return $value . 's';
    }
    
    /**
     * Get the singular form of the given word
     *
     * @param string $value
     * @return string
     */
    protected static function singularize($value)
    {
        $last = strtolower(substr($value, -3));
        
        // Words ending in -ies (e.g., categories -> category)
        if (substr($value, -3) === 'ies') {
            return substr($value, 0, -3) . 'y';
        }
        
        // Words ending in -es (e.g., boxes -> box)
        if (in_array(substr($value, -2), ['es']) || 
            in_array(substr($value, -1), ['s', 'x', 'z']) || 
            in_array(substr($value, -2), ['ch', 'sh'])) {
            return substr($value, 0, -2);
        }
        
        // Words ending in -s (e.g., users -> user)
        if (substr($value, -1) === 's') {
            return substr($value, 0, -1);
        }
        
        return $value;
    }
    
    /**
     * Generate a URL friendly "slug" from a given string
     *
     * @param string $title
     * @param string $separator
     * @return string
     */
    public static function slug($title, $separator = '-')
    {
        // Convert all dashes/underscores into separator
        $flip = $separator === '-' ? '_' : '-';
        
        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);
        
        // Replace @ with the word 'at'
        $title = str_replace('@', $separator . 'at' . $separator, $title);
        
        // Remove all characters that are not the separator, letters, numbers, or whitespace
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', static::lower($title));
        
        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);
        
        return trim($title, $separator);
    }
    
    /**
     * Limit the number of characters in a string
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }
        
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }
    
    /**
     * Limit the number of words in a string
     *
     * @param string $value
     * @param int $words
     * @param string $end
     * @return string
     */
    public static function words($value, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);
        
        if (!isset($matches[0]) || strlen($value) === strlen($matches[0])) {
            return $value;
        }
        
        return rtrim($matches[0]) . $end;
    }
    
    /**
     * Generate a random string of the specified length
     *
     * @param int $length
     * @return string
     */
    public static function randomString($length = 16)
    {
        $string = '';
        
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        
        return $string;
    }
    
    /**
     * Generate a more secure random string
     *
     * @param int $length
     * @return string
     */
    public static function secureRandomString($length = 16)
    {
        return bin2hex(random_bytes($length / 2));
    }
}
