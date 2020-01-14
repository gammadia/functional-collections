<?php

declare(strict_types=1);

namespace Gammadia\Collections\Functional;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

final class UseFunctionalFunctionsPhpStanRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        $functionName = $node->name->toString();
        $functionsToReplace = FUNCTIONS_REPLACEMENTS_MAP;

        if (isset($functionsToReplace[$functionName])) {
            return [
                sprintf(
                    'Please <info>use function %s;</info> instead of PHP\'s %s().',
                    $functionsToReplace[$functionName],
                    $functionName
                ),
            ];
        }

        return [];
    }
}
