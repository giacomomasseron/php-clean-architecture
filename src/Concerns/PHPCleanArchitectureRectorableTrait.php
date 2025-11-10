<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Concerns;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Reflection\ClassReflection;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;

/**
 * @mixin AbstractRector
 */
trait PHPCleanArchitectureRectorableTrait
{
    /**
     * @var string[]
     */
    private array $targetNamespaces = [];

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    private function doRefactor(Node $node, string $interfaceNamespace): ?Node
    {
        if (! $node instanceof Class_) {
            return null;
        }

        if ($this->isAlreadyImplementing($node, $interfaceNamespace)) {
            return null;
        }

        $scope = ScopeFetcher::fetch($node);

        // Get the class reflection from scope
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }

        // Check if the class belongs to one of the target namespaces
        if (!$this->isInTargetNamespace($classReflection)) {
            return null;
        }

        $node->implements[] = new Node\Name\FullyQualified($interfaceNamespace);

        return $node;
    }

    /**
     * @param array<string, list<string>> $configuration
     * @return void
     */
    public function configure(array $configuration): void
    {
        $this->targetNamespaces = $configuration['targetNamespaces'] ?? [];
    }

    private function isAlreadyImplementing(Class_ $class, string $interface): bool
    {
        foreach ($class->implements as $implement) {
            if ($this->isName($implement, $interface)) {
                return true;
            }
        }
        return false;
    }

    private function isInTargetNamespace(ClassReflection $classReflection): bool
    {
        $className = $classReflection->getName();

        foreach ($this->targetNamespaces as $namespace) {
            // Check if class name starts with the target namespace
            if (str_starts_with($className, $namespace . '\\') || $className === $namespace) {
                return true;
            }
        }

        return false;
    }
}
