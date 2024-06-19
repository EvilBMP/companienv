<?php

namespace Companienv\Interaction;

use Companienv\Companion;
use Companienv\DotEnv\Block;
use Companienv\DotEnv\Variable;
use Companienv\Extension;

class AskVariableValues implements Extension
{
    /**
     * {@inheritdoc}
     */
    public function getVariableValue(Companion $companion, Block $block, Variable $variable): string
    {
        $definedVariablesHash = $companion->getDefinedVariablesHash();
        $defaultValue = (string)($definedVariablesHash[$variable->getName()] ?? $variable->getValue());
        $defaultValue = (string)($defaultValue !== '' ? $defaultValue : $variable->getValue());
        $question = sprintf('<comment>%s</comment> ? ', $variable->getName());

        if ($defaultValue !== '') {
            $question .= '(' . $defaultValue . ') ';
        }

        return $companion->ask($question, $defaultValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isVariableRequiringValue(Companion $companion, Block $block, Variable $variable, string $currentValue = null): int
    {
        return (
            $currentValue === null || (
                $currentValue === '' && $variable->getValue() !== ''
            )
        ) ? Extension::VARIABLE_REQUIRED : Extension::ABSTAIN;
    }
}
