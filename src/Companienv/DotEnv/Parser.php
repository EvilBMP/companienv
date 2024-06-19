<?php

namespace Companienv\DotEnv;

use Companienv\IO\FileSystem\FileSystem;

class Parser
{
    public function parse(FileSystem $fileSystem, string $path): File
    {
        $blocks = [];

        /** @var Block|null $block */
        $block = null;
        foreach (explode("\n", $fileSystem->getContents($path)) as $number => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '#')) {
                // We see a title
                if (substr($line, 0, 2) === '##') {
                    $block = new Block(trim($line, '# '));
                    $blocks[] = $block;
                } elseif (substr($line, 0, 2) === '#~') {
                    // Ignore this comment.
                } elseif ($block !== null) {
                    if (substr($line, 0, 2) === '#+') {
                        $block->addAttribute($this->parseAttribute(substr($line, 2)));
                    } elseif (substr($line, 1, 1) === ' ') {
                        $block->appendToDescription(trim($line, '# '));
                    }
                }
            } elseif (false !== ($firstEquals = strpos($line, '='))) {
                if ($block === null) {
                    $blocks[] = $block = new Block();
                }

                $block->addVariable(new Variable(
                    substr($line, 0, $firstEquals),
                    substr($line, $firstEquals + 1)
                ));
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'The line %d of the file %s is invalid: %s',
                    $number,
                    $path,
                    $line
                ));
            }
        }

        return new File('', $blocks);
    }

    private function parseAttribute(string $string): Attribute
    {
        $variableNameRegex = '[A-Z0-9_]+';
        $valueRegex = '[^\) ]+';

        if (preg_match('/^([a-z0-9-]+)\(((' . $variableNameRegex . ' ?)*)\)(:\(((' . $variableNameRegex . '=' . $valueRegex . ' ?)*)\))?$/', $string, $matches) === false) {
            throw new \RuntimeException(sprintf(
                'Unable to parse the given attribute: %s',
                $string
            ));
        }

        return new Attribute($matches[1], explode(' ', $matches[2]), isset($matches[6]) ? $this->dotEnvMappingToKeyBasedMapping($matches[6]) : []);
    }

    /**
     * @return array<string, mixed>
     */
    private function dotEnvMappingToKeyBasedMapping(string $dotEnvMapping): array
    {
        $mapping = [];
        $envMappings = explode(' ', $dotEnvMapping);

        foreach ($envMappings as $envMapping) {
            if (!str_contains($envMapping, '=')) {
                throw new \RuntimeException(sprintf(
                    'Could not parse attribute mapping "%s"',
                    $dotEnvMapping
                ));
            }

            [$key, $value] = explode('=', $envMapping);
            $mapping[$key] = $value;
        }

        return $mapping;
    }
}
