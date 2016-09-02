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
    const QUESTION_PLACEHOLDER_DEFAULT = '%default%';
    const QUESTION_PLACEHOLDER_DEFAULT_VALUE = '%value%';
    const QUESTION_PLACEHOLDER_BOOLEAN_TRUE = '%true%';
    const QUESTION_PLACEHOLDER_BOOLEAN_FALSE = '%false%';
    const QUESTION_PATTERN_DEFAULT = '[default: %value%]';
    const QUESTION_PATTERN_DEFAULT_BOOLEAN = '[%true%/%false%]';
    const QUESTION_SUFFIX = ': ';

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
                null,
                InputOption::VALUE_REQUIRED,
                $this->getConfigValue('description', $config, '')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        foreach($this->getOptionsConfig() as $name => $config) {
            $value = $input->getOption($name);

            if ($value === null
                && $this->getConfigValue('isRequired', $config, false)
                && !array_key_exists('defaultValue', $config)
            ) {
                throw new \Exception(sprintf('Option "%s" is required!', $name));
            }

            if (!$this->getConfigValue('isInitial', $config, false)) {
                $value = $value !== null ? $value : $this->getConfigValue('defaultValue', $config);
                $input->setOption(
                    $name,
                    $this->getConfigValue('isBoolean', $config, false)
                        ? preg_match(sprintf('~^%s~i', static::SYMBOL_BOOLEAN_TRUE), $value) ? true : false
                        : $value
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        foreach($this->getOptionsConfig() as $name => $config) {
            if ($this->getConfigValue('isInitial', $config, false)) {
                $this->fillOption($input, $output, $name);
            }
        }
    }

    /**
     * Request option interactively if it was not specified in command line
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $name
     * @return $this
     * @throws \Exception
     */
    protected function fillOption(InputInterface $input, OutputInterface $output, $name)
    {
        $config = $this->getConfigValue($name, $this->getOptionsConfig());

        if (!$config) {
            throw new \Exception(sprintf('Config for option "%s" does not exist!', $name));
        }

        $question = $this->getConfigValue('question', $config);

        if (!is_string($question)) {
            throw new \Exception(sprintf('Option "%s" has no question and cannot be set interactively!', $name));
        }

        if ($input->getOption($name) === null) {
            $isBoolean = $this->getConfigValue('isBoolean', $config, false);
            $defaultValue = $this->getConfigValue('defaultValue', $config);

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
                ? new ConfirmationQuestion($question, $defaultValue, sprintf('~^%s~i', static::SYMBOL_BOOLEAN_TRUE))
                : new Question($question, $defaultValue);

            $input->setOption($name, $this->getQuestionHelper()->ask($input, $output, $question));
            $output->writeln($isBoolean ? ($input->getOption($name) ? 'yes' : 'no') : $input->getOption($name));
        }

        return $this;
    }

    /**
     * Execute shell command
     *
     * @param OutputInterface $output
     * @param array|string $commands
     * @return array
     */
    protected function shell(OutputInterface $output, $commands)
    {
        $commands = (array)$commands;
        $returnCodes = [];

        foreach ($commands as $command) {
            $output->writeln(['Executing shell command:', $command]);
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
    protected function getConfigValue($optionName, array $config, $defaultValue = null)
    {
        return array_key_exists($optionName, $config) ? $config[$optionName] : $defaultValue;
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
