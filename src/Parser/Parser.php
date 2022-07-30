<?php

declare(strict_types=1);

namespace Ghostwriter\Code\Parser;

use Generator;
use Ghostwriter\Code\Contract\ParserInterface;
use Ghostwriter\Code\Exception\ParserErrorsException;
use Ghostwriter\Code\Exception\ShouldNotHappenException;
use Ghostwriter\Collection\Collection;
use Ghostwriter\Container\Container;
use Ghostwriter\Option\None;
use Ghostwriter\Option\Some;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\Lexer\Emulative;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;

final class Parser implements ParserInterface
{
    /**
     * @var array<string, ClassLike[]>
     */
    private array $classesByFilename = [];

    private Collection $files;

    private Lexer $lexer;

    private NameResolver $nameResolver;

    private NodeTraverser $nodeTraverser;

    /**
     * @var Collection<NodeVisitor>
     */
    private Collection $nodeVisitors;

    private PhpParser $parser;

    private ParserFactory $parserFactory;

    public function __construct(
        private Container $container
    ) {
        /** @var Collection<NodeVisitor> $this->nodeVisitors */
        $this->nodeVisitors = Collection::fromGenerator(
            function (): Generator {
                yield new NameResolver();
                /** @var class-string<NodeVisitor> $nodeVisitor */
                foreach ($this->container->tagged(NodeVisitor::class) as $nodeVisitor) {
                    yield $this->container->get($nodeVisitor);
                }
            }
        );

        $this->parserFactory = new ParserFactory();
        $this->lexer = new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'],
        ]);

        $this->parser = $this->parserFactory->create(ParserFactory::PREFER_PHP7, $this->lexer);
    }

    /**
     * @return Collection<NodeVisitor>
     */
    public function getNodeVisitors(): Collection
    {
        return $this->nodeVisitors;
    }

    public function parse(string $code): Collection
    {
        $errorHandler = new Collecting();

        $nodes = $this->parser->parse($code, $errorHandler);

        if ($errorHandler->hasErrors()) {
            throw new ParserErrorsException($errorHandler->getErrors(), None::create());
        }

        if (null === $nodes) {
            throw new ShouldNotHappenException();
        }

        $nodeVisitors = 0;
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver($errorHandler, [
            'preserveOriginalNames' => false,
            'replaceNodes' => true,
        ]));

        foreach ($this->nodeVisitors as $nodeVisitor) {
            $nodeTraverser->addVisitor($nodeVisitor);
            ++$nodeVisitors;
        }

        if (0 === $nodeVisitors) {
            throw new ShouldNotHappenException('No Visitors registered!');
        }

        /** @var Collection<Stmt> */
        return Collection::fromIterable($nodeTraverser->traverse($nodes));
    }

    /**
     * @return Collection<Stmt>
     */
    public function parseFile(string $file): Collection
    {
        try {
            return $this->parse($file);
        } catch (ParserErrorsException $parserErrorsException) {
            throw new ParserErrorsException($parserErrorsException->getErrors(), Some::create($file));
        }
    }
}
