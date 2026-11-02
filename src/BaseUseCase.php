<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture;

use BadMethodCallException;
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\UseCaseExecutorInterface;
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\UseCaseInterface;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseCompletedEvent;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseStartedEvent;
use GiacomoMasseroni\PHPCleanArchitecture\Exceptions\PHPCleanArchitectureException;
use GiacomoMasseroni\PHPCleanArchitecture\Exceptions\UseCaseNotStaticallyRunnableException;
use ReflectionClass;

/**
 * @method static mixed run(mixed ...$arguments)
 */
abstract class BaseUseCase implements UseCaseInterface
{
    public ?UseCaseExecutorInterface $executor = null;

    /**
     * @var list<mixed>
     */
    protected array $data = [];

    /**
     * @throws UseCaseNotStaticallyRunnableException
     */
    private static function getInstance(): static
    {
        $class = new ReflectionClass(static::class);

        if ($class->getConstructor()?->getNumberOfRequiredParameters() > 0) {
            throw new UseCaseNotStaticallyRunnableException(static::class);
        }

        return $class->newInstance();
    }

    /**
     * Set the user executing the use case
     *
     * @throws UseCaseNotStaticallyRunnableException
     */
    public static function actingAs(?UseCaseExecutorInterface $user): static
    {
        $useCase = self::getInstance();
        $useCase->executor = $user;

        return $useCase;
    }

    /**
     * @throws PHPCleanArchitectureException
     */
    public function __invoke(mixed ...$arguments): mixed
    {
        Dispatcher::getInstance()->dispatch(new UseCaseStartedEvent($this));

        try {
            $result = $this->handle(...$arguments);
        } catch (PHPCleanArchitectureException $exception) {
            $this->rollback();

            throw $exception;
        }

        Dispatcher::getInstance()->dispatch(new UseCaseCompletedEvent($this));

        return $result;
    }

    public function rollback(): void {}

    /**
     * @param string $name
     * @param list<mixed> $arguments
     * @return mixed
     * @throws PHPCleanArchitectureException
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        if ($name === 'run') {
            return self::getInstance()->__invoke(...$arguments);
        }

        throw new BadMethodCallException("Method {$name} does not exist.");
    }

    /**
     * @param string $name
     * @param list<mixed> $arguments
     * @return mixed
     * @throws PHPCleanArchitectureException
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($name === 'run') {
            return $this->__invoke(...$arguments);
        }

        throw new BadMethodCallException("Method {$name} does not exist.");
    }
}
