<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Abstract command for all devbox commands
 */
abstract class AbstractCommand extends Command
{
    /**#@+
     * Words and symbols
     */
    const WORD_BOOLEAN_TRUE = 'yes';
    const WORD_BOOLEAN_FALSE = 'no';
    const SYMBOL_BOOLEAN_TRUE = 'y';
    const SYMBOL_BOOLEAN_FALSE = 'n';
    /**#@-*/

    /**#@+
     * Question element patterns
     */
    const QUESTION_PATTERN = '%message%: ';
    const QUESTION_PATTERN_DEFAULT = '[default: %value%]';
    const QUESTION_PATTERN_DEFAULT_BOOLEAN = '[%true%/%false%]';
    /**#@-*/

    /**#@+
     * Question pattern placeholders
     */
    const QUESTION_PLACEHOLDER_MESSAGE = '%message%';
    const QUESTION_PLACEHOLDER_DEFAULT = '%default%';
    const QUESTION_PLACEHOLDER_DEFAULT_VALUE = '%value%';
    const QUESTION_PLACEHOLDER_BOOLEAN_TRUE = '%true%';
    const QUESTION_PLACEHOLDER_BOOLEAN_FALSE = '%false%';
    /**#@-*/

    /**#@+
     * Value matchers
     */
    const MATCHER_BOOLEAN_TRUE = '~^(?:[1y]|yes|true)$~i';
    /**#@-*/

    /**#@+
     * Option defaults
     */
    const OPTION_DEFAULT_INITIAL = false;
    const OPTION_DEFAULT_VIRTUAL = false;
    const OPTION_DEFAULT_BOOLEAN = false;
    const OPTION_DEFAULT_REQUIRE_VALUE = true;
    /**#@-*/

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * @var array
     */
    private $valueSetStates = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        foreach($this->getOptionsConfig() as $name => $config) {
            if (!$this->getConfigValue('virtual', $config, static::OPTION_DEFAULT_VIRTUAL)) {
                $this->addOption(
                    $name,
                    $this->getConfigValue('shortcut', $config),
                    $this->getConfigValue('requireValue', $config, static::OPTION_DEFAULT_REQUIRE_VALUE)
                        && !$this->getConfigValue('boolean', $config, static::OPTION_DEFAULT_BOOLEAN)
                        ? InputOption::VALUE_REQUIRED
                        : InputOption::VALUE_OPTIONAL,
                    $this->getConfigValue('description', $config, ''),
                    $this->getConfigValue('default', $config)
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        foreach($this->getOptionsConfig() as $name => $config) {
            if ($input->hasParameterOption('--' . $name)) {
                $this->valueSetStates[$name] = true;

                if ($this->getConfigValue('boolean', $config, static::OPTION_DEFAULT_BOOLEAN)) {
                    $input->setOption($name, $this->isTrue($input->getOption($name)));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        foreach($this->getOptionsConfig() as $name => $config) {
            if ($this->getConfigValue('initial', $config, static::OPTION_DEFAULT_INITIAL)
                && !$this->getConfigValue('virtual', $config, static::OPTION_DEFAULT_VIRTUAL)
                && !$this->getConfigValue($name, $this->valueSetStates, false)
            ) {
                $this->requestOption($name, $input, $output);
            }
        }
    }

    /**
     * Request option interactively
     *
     * @param string $name
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $overwrite
     * @param string|null $question
     * @return mixed
     * @throws \Exception
     */
    protected function requestOption(
        $name,
        InputInterface $input,
        OutputInterface $output,
        $overwrite = false,
        $question = null
    ) {
        $config = $this->getConfigValue($name, $this->getOptionsConfig());

        if (!$config) {
            throw new \Exception(sprintf('Config for option "%s" does not exist!', $name));
        }

        $question = $question === null ? $this->getConfigValue('question', $config) : $question;

        if (!is_string($question)) {
            throw new \Exception(sprintf('Option "%s" has no question and cannot be set interactively!', $name));
        }

        if ($this->getConfigValue($name, $this->valueSetStates, false) && !$overwrite) {
            return $input->getOption($name);
        }

        $isBoolean = $this->getConfigValue('boolean', $config, static::OPTION_DEFAULT_BOOLEAN);
        $defaultValue = $this->getConfigValue('default', $config);

        if ($isBoolean) {
            $defaultValueString = static::QUESTION_PATTERN_DEFAULT_BOOLEAN;
            $defaultValueString = str_replace(
                static::QUESTION_PLACEHOLDER_BOOLEAN_TRUE,
                $defaultValue ? strtoupper(static::SYMBOL_BOOLEAN_TRUE) : static::SYMBOL_BOOLEAN_TRUE,
                $defaultValueString
            );
            $defaultValueString = str_replace(
                static::QUESTION_PLACEHOLDER_BOOLEAN_FALSE,
                !$defaultValue ? strtoupper(static::SYMBOL_BOOLEAN_FALSE) : static::SYMBOL_BOOLEAN_FALSE,
                $defaultValueString
            );
        } else {
            $defaultValueString = str_replace(
                static::QUESTION_PLACEHOLDER_DEFAULT_VALUE,
                $defaultValue,
                static::QUESTION_PATTERN_DEFAULT
            );
        }

        $question = str_replace(static::QUESTION_PLACEHOLDER_DEFAULT, $defaultValueString, $question);
        $question = str_replace(static::QUESTION_PLACEHOLDER_MESSAGE, $question, static::QUESTION_PATTERN);
        $question = $isBoolean
            ? new ConfirmationQuestion($question, $defaultValue, static::MATCHER_BOOLEAN_TRUE)
            : new Question($question, $defaultValue);
        $value = $this->getQuestionHelper()->ask($input, $output, $question);

        if (!$this->getConfigValue('virtual', $config, static::OPTION_DEFAULT_VIRTUAL)) {
            $this->valueSetStates[$name] = true;
            $input->setOption($name, $value);
        }

        $output->writeln($isBoolean ? ($value ? static::WORD_BOOLEAN_TRUE : static::WORD_BOOLEAN_FALSE) : $value);

        return $value;
    }

    /**
     * Execute shell commands
     *
     * @param array|string $commands
     * @param OutputInterface|null $output
     * @return array
     * @throws \Exception
     */
    protected function executeCommands($commands, OutputInterface $output = null)
    {
        $commands = (array)$commands;
        $returnCodes = [];

        foreach ($commands as $command) {
            if ($output) {
                $output->writeln(['Executing shell command:', $command]);
            }

            $commandOutput = [];
            $returnCode = 0;
            exec($command, $commandOutput, $returnCode);
            $returnCodes[] = $returnCode;
            $output = implode("\n", $commandOutput) . "\n";
            if ($returnCode > 0) {
                throw new \Exception('Command failed to execute');
            } else {
                echo $output;
            }
        }
        return $returnCodes;
    }

    /**
     * @param string $command
     * @return bool
     */
    protected function commandExist($command)
    {
        $result = shell_exec('which ' . $command);
        return (empty($result) ? false : true);
    }

    /**
     * Get config value
     *
     * @param string $optionName
     * @param array $config
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function getConfigValue($optionName, $config, $defaultValue = null)
    {
        return is_array($config) && array_key_exists($optionName, $config) ? $config[$optionName] : $defaultValue;
    }

    /**
     * Check if input string matches "true" pattern
     *
     * @param $string
     * @return bool
     */
    protected function isTrue($string)
    {
        return (bool)preg_match(static::MATCHER_BOOLEAN_TRUE, $string);
    }

    /**
     * Get question helper
     *
     * @return QuestionHelper
     */
    protected function getQuestionHelper()
    {
        if ($this->questionHelper === null) {
            $this->questionHelper = $this->getHelper('question');
        }

        return $this->questionHelper;
    }

    /**
     * Get configuration for input options
     *
     * Config parameters:
     * - virtual        virtual options are not added into the list of supported options for this command and their
     *                  values are not stored
     * - initial        whether to request for option automatically before command execution (does not support virtual
     *                  options)
     * - requireValue   whether to allow this option to be passed as argument with empty value (e.g. "--option" or
     *                  "--option=")
     * - boolean        whether this option is of boolean type (boolean option values are converted into boolean type)
     * - default        default value for this option if not requested or left empty
     * - shortcut       argument shortcut (e.g. -h for --help)
     * - description    argument description
     * - question       default question for interactive option request
     *
     * @return array
     */
    protected function getOptionsConfig()
    {
        return [];
    }
}
