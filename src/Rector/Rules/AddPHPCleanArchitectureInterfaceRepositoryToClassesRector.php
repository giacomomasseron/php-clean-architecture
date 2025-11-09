<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Rector\Rules;

use GiacomoMasseroni\PHPCleanArchitecture\Concerns\PHPCleanArchitectureRectorableTrait;
use PhpParser\Node;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class AddPHPCleanArchitectureInterfaceRepositoryToClassesRector extends AbstractRector implements ConfigurableRectorInterface
{
    use PHPCleanArchitectureRectorableTrait;

    /**
     * @throws PoorDocumentationException
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add interface RepositoryInterface to classes matching conditions', []);
    }

    public function refactor(Node $node): ?Node
    {
        return $this->doRefactor($node, 'GiacomoMasseroni\PHPCleanArchitecture\Contracts\RepositoryInterface');
    }
}
