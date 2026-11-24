<?php

declare(strict_types=1);

namespace IanFlanagan\PhpIanfStrictBaseline\Adapters\Driving\PhpStan;

use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Calls;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\CannotResolve;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Loads;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Performs;
use IanFlanagan\PhpIanfStrictBaseline\Core\Reach\Step;
use JsonException;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

/**
 * Reads the code outside the core that the core reaches, a file at a time, into a graph of what
 * each function, method and file's load-time code does. Each file is parsed with PHPStan's
 * parser and walked with its scope resolver, so a call there is resolved as PHPStan resolves one
 * in the project's own code. What it has read it keeps for the rest of the run, so each file of
 * a package is read once in each PHPStan process.
 */
final class CodeReach
{
    /** @var array<string, list<Step>> what each piece of code read so far does, by its key */
    private array $graph = [];

    /** @var array<string, true> the files read so far */
    private array $read = [];

    /** @var array<string, list<Loads|CannotResolve>> each package directory's Composer autoload files, as loads */
    private array $packageLoads = [];

    public function __construct(
        private readonly Parser $parser,
        private readonly NodeScopeResolver $resolver,
        private readonly ScopeFactory $scopes,
        private readonly CallSteps $steps,
        private readonly string $root,
    ) {
    }

    /**
     * The graph, with what $start reaches read into it, breadth first, until a piece of code that
     * performs an impure operation or makes a call that can't be resolved is read. So the graph
     * holds the shortest path to one, or, when there is none, everything $start reaches.
     *
     * @return array<string, list<Step>>
     */
    public function from(Calls|Loads $start): array
    {
        $pending = [$start];
        $visited = [];
        while ($pending !== []) {
            $step = array_shift($pending);
            [$key, $file] = $step instanceof Calls ? [$step->target, $step->file] : [$step->file, $step->file];
            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;
            $this->readFile(self::resolved($file));
            $this->graph[$key] ??= [CannotResolve::call($step->label.", whose code the check can't read, as a trait's or code built at run time is")];
            foreach ($this->graph[$key] as $next) {
                if ($next instanceof Performs || $next instanceof CannotResolve) {
                    return $this->graph;
                }
                $pending[] = $next;
            }
        }

        return $this->graph;
    }

    private function readFile(string $file): void
    {
        if (isset($this->read[$file])) {
            return;
        }
        $this->read[$file] = true;
        $this->graph[$file] = $this->packageLoadsOf($file);
        try {
            $statements = is_file($file) ? $this->parser->parseFile($file) : null;
        } catch (ParserErrorsException) {
            $statements = null;
        }
        if ($statements === null) {
            $this->graph[$file][] = CannotResolve::call('loads '.$this->relative($file).", which the check can't read");

            return;
        }
        $this->declare($statements, $file);
        $this->resolver->processNodes(
            $statements,
            $this->scopes->create(ScopeContext::create($file)),
            function (Node $node, Scope $scope) use ($file): void {
                $key = self::owner($scope, $file);
                foreach ($this->steps->of($node, $scope) as $step) {
                    $this->graph[$key][] = $step;
                }
            },
        );
    }

    /**
     * Enters each named function and method a file declares in the graph, with the load of the
     * file, so that one that does nothing still counts as read. A trait's methods are left out:
     * PHPStan walks them only in a class that uses the trait, and only in the files it analyses,
     * so what they do is never read.
     *
     * @param  array<Node>  $statements
     */
    private function declare(array $statements, string $file): void
    {
        $load = [Loads::file($file, 'loads '.$this->relative($file))];
        $finder = new NodeFinder();
        foreach ($finder->findInstanceOf($statements, Stmt\Function_::class) as $function) {
            if ($function->namespacedName !== null) {
                $this->graph[CallSteps::functionKey($function->namespacedName->toString())] ??= $load;
            }
        }
        foreach ($finder->findInstanceOf($statements, Stmt\ClassLike::class) as $class) {
            if ($class->namespacedName === null || $class instanceof Stmt\Trait_) {
                continue;
            }
            foreach ($class->getMethods() as $method) {
                $this->graph[CallSteps::methodKey($class->namespacedName->toString(), $method->name->toString())] ??= $load;
            }
        }
    }

    /**
     * The key of the code a node belongs to: its function or method, a closure's counting as the
     * function it is written in, or the file's load-time code.
     */
    private static function owner(Scope $scope, string $file): string
    {
        $function = $scope->getFunction();
        $class = $scope->getClassReflection();

        return match (true) {
            $function === null => $file,
            $class !== null => CallSteps::methodKey($class->getName(), $function->getName()),
            default => CallSteps::functionKey($function->getName()),
        };
    }

    /**
     * What loading any file of a package loads too: the files its composer.json's autoload
     * files entry names, which Composer runs when the autoloader is included. The package is the
     * nearest directory above the file, up to the project's root, with a composer.json.
     *
     * @return list<Step>
     */
    private function packageLoadsOf(string $file): array
    {
        $directory = dirname($file);
        while (! is_file("{$directory}/composer.json")) {
            $parent = dirname($directory);
            if ($parent === $directory || ! str_starts_with($directory, rtrim($this->root, '/').'/')) {
                return [];
            }
            $directory = $parent;
        }
        $this->packageLoads[$directory] ??= $this->autoloadFiles($directory);

        return array_values(array_filter(
            $this->packageLoads[$directory],
            static fn (Loads|CannotResolve $load): bool => ! $load instanceof Loads || $load->file !== $file,
        ));
    }

    /**
     * @return list<Loads|CannotResolve>
     */
    private function autoloadFiles(string $directory): array
    {
        $manifest = "{$directory}/composer.json";
        try {
            $composer = json_decode((string) file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [CannotResolve::call('loads the autoload files of '.$this->relative($manifest).", which isn't JSON")];
        }
        $files = is_array($composer) && is_array($composer['autoload'] ?? null) ? ($composer['autoload']['files'] ?? []) : [];
        $loads = [];
        foreach (is_array($files) ? $files : [] as $entry) {
            if (is_string($entry)) {
                $path = self::resolved("{$directory}/{$entry}");
                $loads[] = Loads::file($path, 'loads '.$this->relative($path));
            }
        }

        return $loads;
    }

    /**
     * A file's path with no `..` or link in it, so that each file has one key however it was
     * reached, or the path as given when there is no such file.
     */
    private static function resolved(string $file): string
    {
        $resolved = realpath($file);

        return $resolved === false ? $file : $resolved;
    }

    private function relative(string $file): string
    {
        $prefix = rtrim($this->root, '/').'/';

        return str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
    }
}
