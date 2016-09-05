<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command;

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
    const SYMBOL_BOOLEAN_TRUE = 'y';
    const SYMBOL_BOOLEAN_FALSE = 'n';
    const WORD_BOOLEAN_TRUE = 'yes';
    const WORD_BOOLEAN_FALSE = 'no';
    const QUESTION_PLACEHOLDER_DEFAULT = '%default%';
    const QUESTION_PLACEHOLDER_DEFAULT_VALUE = '%value%';
    const QUESTION_PLACEHOLDER_BOOLEAN_TRUE = '%true%';
    const QUESTION_PLACEHOLDER_BOOLEAN_FALSE = '%false%';
    const QUESTION_PATTERN_DEFAULT = '[default: %value%]';
    const QUESTION_PATTERN_DEFAULT_BOOLEAN = '[%true%/%false%]';
    const PARAMETER_BOOLEAN_TRUE_PATTERN = '~^(?:[1y]|yes|true)$~i';
    const QUESTION_SUFFIX = ': ';
    const OPTION_DEFAULT_OPENING = false;
    const OPTION_DEFAULT_BOOLEAN = false;
    const OPTION_DEFAULT_VALUE_REQUIRED = true;
    const OPTION_DEFAULT_VERBOSE = true;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        foreach($this->getOptionsConfig() as $name => $config) {
            $this->addOption(
                $name,
                $this->getConfigValue('shortcut', $config),
                $this->getConfigValue('valueRequired', $config, static::OPTION_DEFAULT_VALUE_REQUIRED)
                    ? InputOption::VALUE_REQUIRED
                    : InputOption::VALUE_OPTIONAL,
                $this->getConfigValue('description', $config, ''),
                $this->getConfigValue('default', $config)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        foreach($this->getOptionsConfig() as $name => $config) {
            if ($input->hasParameterOption('--' . $name)
                && $this->getConfigValue('boolean', $config, static::OPTION_DEFAULT_BOOLEAN)
            ) {
                $input->setOption($name, $this->isTrue($input->getOption($name)));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        foreach($this->getOptionsConfig() as $name => $config) {
            if (!$input->hasParameterOption('--' . $name)
                && $this->getConfigValue('opening', $config, static::OPTION_DEFAULT_OPENING)
            ) {
                $this->requestOption($name, $input, $output);
            }
        }
    }

    /**
     * Request option interactively if it was not specified in command line
     *
     * @param string $name
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     * @throws \Exception
     */
    protected function requestOption($name, InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfigValue($name, $this->getOptionsConfig());

        if (!$config) {
            throw new \Exception(sprintf('Config for option "%s" does not exist!', $name));
        }

        $question = $this->getConfigValue('question', $config);

        if (!is_string($question)) {
            throw new \Exception(sprintf('Option "%s" has no question and cannot be set interactively!', $name));
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

        $question = str_replace(static::QUESTION_PLACEHOLDER_DEFAULT, $defaultValueString, $question)
            . static::QUESTION_SUFFIX;
        $question = $isBoolean
            ? new ConfirmationQuestion($question, $defaultValue, static::PARAMETER_BOOLEAN_TRUE_PATTERN)
            : new Question($question, $defaultValue);
        $input->setOption($name, $this->getQuestionHelper()->ask($input, $output, $question));
        $output->writeln(
            $isBoolean
                ? ($input->getOption($name) ? static::WORD_BOOLEAN_TRUE : static::WORD_BOOLEAN_FALSE)
                : $input->getOption($name)
        );

        return $this;
    }

    /**
     * Execute shell commands
     *
     * @param array|string $commands
     * @param OutputInterface|null $output
     * @return array
     */
    protected function executeCommands($commands, OutputInterface $output = null)
    {
        $commands = (array)$commands;
        $returnCodes = [];

        foreach ($commands as $command) {
            if ($output) {
                $output->writeln(['Executing shell command:', $command]);
            }

            passthru($command, $return);
            $returnCodes[] = $return;
        }

        return $returnCodes;
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
        return (bool)preg_match(static::PARAMETER_BOOLEAN_TRUE_PATTERN, $string);
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
     * @return array
     */
    protected abstract function getOptionsConfig();
}
