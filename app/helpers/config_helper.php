<?php

if (!function_exists('config')) {
    /**
     * Get the specified configuration value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        static $config = [];
        
        // If the configuration hasn't been loaded yet, load it
        if (empty($config)) {
            // Load the main app config
            $appConfigPath = __DIR__ . '/../../config/app.php';
            if (file_exists($appConfigPath)) {
                $config = array_merge($config, require $appConfigPath);
            }
            
            // You can load other config files here as needed
            // Example: $config = array_merge($config, require __DIR__ . '/../../config/database.php');
        }
        
        // If no key is provided, return the entire config
        if (is_null($key)) {
            return $config;
        }
        
        // If the key is a direct match, return it
        if (array_key_exists($key, $config)) {
            return $config[$key];
        }
        
        // Support dot notation for nested arrays (e.g., 'app.name')
        if (strpos($key, '.') !== false) {
            $array = $config;
            $keys = explode('.', $key);
            
            foreach ($keys as $segment) {
                if (is_array($array) && array_key_exists($segment, $array)) {
                    $array = $array[$segment];
                } else {
                    return value($default);
                }
            }
            
            return $array;
        }
        
        return value($default);
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return value($default);
        }
        
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        if (strlen($value) > 1 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
}

// Helper function to check if a string starts with a given substring
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

// Helper function to check if a string ends with a given substring
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
}
