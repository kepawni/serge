<?php
namespace deswerve\colin;

use RuntimeException;

/**
 * Can parse command line invocations like given in the following example and returns an stdClass descriptor for them.
 *
 * php myscript.php -vvv -x=1 -y2 -z 3 --desc 'some description' /home/user/example.txt --point 2.1 3.14 5.7
 * Result as JSON:
 * {"options":{
 *   "verbose":{"values":[],"count":3},
 *   "x-value":{"values":["1"],"count":1},
 *   "y-value":{"values":["2"],"count":1},
 *   "z-value":{"values":["3"],"count":1},
 *   "point":{"values":["2.1","3.14","5.7"],"count":1},
 *   "desc":{"values":["some description"],"count":1}
 * },"params":["/home/user/example.txt"]}
 */
class CommandLineInterface
{
    private $applicationName = '';
    private $optionNames = [];
    private $options = [];
    private $usageLines = [];

    /**
     * @param string $applicationName The name of the executable, e. g. "mount"
     * @param string[] $usageLines The brief notation how to invoke it, e. g. ["[-lhV]", "-a [options]", ...]
     */
    public function __construct($applicationName, $usageLines)
    {
        $this->applicationName = $applicationName;
        $this->usageLines = $usageLines;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result = 'Usage:' . PHP_EOL;
        foreach ($this->usageLines as $line) {
            $result .= sprintf('  %s %s%s', $this->applicationName, $line, PHP_EOL);
        }
        $mandatory = $this->formatOptionReference(false);
        $optional = $this->formatOptionReference(true);
        return sprintf(
            '%s%s%s',
            $result,
            $mandatory ? sprintf('%2$sOptions (required):%2$s  %s%2$s', implode(PHP_EOL . '  ', $mandatory), PHP_EOL) : '',
            $optional ? sprintf('%2$sOptions (optional):%2$s  %s%2$s', implode(PHP_EOL . '  ', $optional), PHP_EOL) : ''
        );
    }

    /**
     * @param string $name The option name, e. g. "verbose" to support a --verbose switch.
     * @param string $description A short description to be printed in a usage documentation.
     * @param string[]|null $params Required params immediately following that option.
     * @param string|null $char A single char name for this option, e. g. "v" to support the -v switch.
     * @param bool $isOptional Whether this option is an optional one that may have a default value.
     * @param mixed[] $defaultValues As many default values as required params or none at all.
     * @return self The same instance for method chaining.
     * @throws RuntimeException
     */
    public function addOption(
        $name,
        $description,
        $params = [],
        $char = null,
        $isOptional = false,
        $defaultValues = []
    ) {
        if ($params === null) {
            $params = [];
        }
        if ($defaultValues === null) {
            $defaultValues = [];
        }
        $this->guardName($name);
        $this->guardChar($char, $name);
        $this->guardParamsAndDefaults($params, $defaultValues, $isOptional, $name);
        $index = count($this->options);
        $this->options[$index] = [$name, $description, $params, $char, $isOptional, $defaultValues];
        $this->optionNames['--' . $name] = $index;
        if ($char && $char !== '') {
            $this->optionNames['-' . $char] = $index;
        }
        return $this;
    }

    /**
     * @param bool|null $havingOptional When true this method will pick only optional options, for false only mandatory
     * ones and for null or when omitted all options are used.
     *
     * @return string[] An array of strings, each describing one option with its switches, params and description.
     */
    public function formatOptionReference(?bool $havingOptional = null)
    {
        $result = [];
        $lines = [];
        $firstColumn = 0;
        foreach ($this->options as $option) {
            list($name, $description, $params, $char, $isOptional, $defaultValues) = $option;
            if ($isOptional === $havingOptional || $havingOptional === null) {
                $shortName = is_null($char) || $char === '' ? '  ' : '-' . $char;
                $first = sprintf('%s  --%s%s', $shortName, $name, $params ? ' ' . implode(' ', $params) : '');
                $firstColumn = max($firstColumn, strlen($first));
                $lines[] = [$first, $description];
            }
        }
        foreach ($lines as $line) {
            $result[] = sprintf('%-' . $firstColumn . 's  %s', $line[0], $line[1]);
        }
        return $result;
    }

    /**
     * @param string[] $argv The original command line arguments as per the $argv global variable (including the executable path itself).
     * @return stdClass A descriptor exposing two arrays: the (named) "options" and (positional) "params". Each item within the "options" array is an stdClass itself exposing a string array "values" and an integer "count" that captures how often that switch had been specified.
     * @throws RuntimeException
     */
    public function processCommandLine($argv)
    {
        $result = (object)[
            'options' => [],
            'params' => []
        ];
        foreach ($this->options as $option) {
            list($name, $description, $params, $char, $isOptional, $defaultValues) = $option;
            $result->options[$name] = (object)[
                'values' => $isOptional && $defaultValues ? $defaultValues : array_fill(0, count($params), null),
                'count' => 0
            ];
        }
        return $this->parse(array_slice($argv, 1), $result);
    }

    /**
     * @param $char
     * @param $name
     * @throws RuntimeException
     */
    private function guardChar($char, $name)
    {
        if (is_null($char) || $char === '') {
            return;
        } elseif (!is_string($char) || strlen($char) > 1) {
            throw new RuntimeException(sprintf('Short option name “%s” for “%s” must be a single char', $char, $name));
        } elseif (isset($this->optionNames['-' . $char])) {
            throw new RuntimeException(sprintf('Duplicate short option name “%s” for “%s”', $char, $name));
        }
    }

    /**
     * @param $name
     * @throws RuntimeException
     */
    private function guardName($name)
    {
        if (isset($this->optionNames['--' . $name])) {
            throw new RuntimeException(sprintf('Duplicate option “%s”', $name));
        } elseif (strlen($name) < 2) {
            throw new RuntimeException(sprintf('Option name “%s” must be at least two characters long', $name));
        }
    }

    /**
     * @param $params
     * @param $defaultValues
     * @param $isOptional
     * @param $name
     * @throws RuntimeException
     */
    private function guardParamsAndDefaults($params, $defaultValues, $isOptional, $name)
    {
        if ($isOptional && is_array($defaultValues) && count($defaultValues) !== count($params)) {
            throw new RuntimeException(
                sprintf(
                    'Number of default values (%d) for “%s” must match its number of params (%d)',
                    count($defaultValues),
                    $name,
                    count($params)
                )
            );
        }
    }

    /**
     * @param string[] $argv
     * @param stdClass $result
     * @return stdClass
     * @throws RuntimeException
     */
    private function parse($argv, $result)
    {
        if (empty($argv)) {
            return $result;
        }
        if (isset($this->optionNames[$argv[0]])) {
            $option = $this->options[$this->optionNames[$argv[0]]];
            list($name, $description, $params, $char, $isOptional, $defaultValues) = $option;
            $result->options[$name]->count++;
            $result->options[$name]->values = array_slice($argv, 1, count($params))
                + $result->options[$name]->values;
            return $this->parse(array_slice($argv, count($params) + 1), $result);
        } elseif (substr($argv[0], 0, 1) === '-' && strlen($argv[0]) > 1) {
            if (substr($argv[0], 0, 2) === '--') {
                throw new RuntimeException(sprintf('Unsupported option “%s”', substr($argv[0], 2)));
            } else {
                $firstCharOption = substr($argv[0], 0, 2);
                $charGroupRemainder = substr($argv[0], 2);
                if (isset($this->optionNames[$firstCharOption])) {
                    $option = $this->options[$this->optionNames[$firstCharOption]];
                    list($name, $description, $params, $char, $isOptional, $defaultValues) = $option;
                    $result->options[$name]->count++;
                    if (count($params)) {
                        $result->options[$name]->values
                            = array_merge([preg_replace('<^[=:]>', '', $charGroupRemainder)],
                                array_slice($argv, 1, count($params) - 1))
                            + $result->options[$name]->values;
                        return $this->parse(array_slice($argv, count($params)), $result);
                    }
                    return $this->parse(array_merge(['-' . $charGroupRemainder], array_slice($argv, 1)), $result);
                } else {
                    throw new RuntimeException(sprintf('Unsupported short option “%s”', substr($firstCharOption, 1)));
                }
            }
        } else {
            $result->params[] = $argv[0];
            return $this->parse(array_slice($argv, 1), $result);
        }
    }
}
