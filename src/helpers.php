<?php

if (! function_exists('class_basename')) {
    /**
     * From Laravel
     *
     * Get the class "basename" of the given object / class.
     *
     */
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}
