<?php
namespace Bookdown\Bookdown;


class CollectorTest extends \PHPUnit_Framework_TestCase
{
    protected $root;
    protected $index;
    protected $page;
    protected $collector;
    protected $stdio;
    protected $fsio;

    protected function setUp()
    {
        $builder = new Builder(
            'php://memory',
            'php://memory',
            'Bookdown\Bookdown\FakeFsio'
        );

        $this->collector = $builder->newCollector();
        $this->stdio = $builder->getStdio();
        $this->fsio = $builder->getFsio();
        $this->setUpFsio();
    }

    protected function setUpFsio()
    {
        $this->fsio->put('/path/to/bookdown.json', '{
            "title": "Example Book",
            "content": {
                "chapter-1": "chapter-1/bookdown.json"
            },
            "target": "/_site"
        }');

        $this->fsio->put('/path/to/chapter-1/bookdown.json', '{
            "title": "Chapter 1",
            "content": {
                "section-1": "section-1.md"
            }
        }');
    }

    public function testCollector()
    {
        $this->root = $this->collector->__invoke('/path/to/bookdown.json');
        $this->index = $this->root->getNext();
        $this->page = $this->index->getNext();

        $this->assertCorrectRoot();
        $this->assertCorrectIndex();
        $this->assertCorrectPage();
    }

    public function assertCorrectRoot()
    {
        $this->assertSame(null, $this->root->getOrigin());
        $this->assertSame('/_site/index.html', $this->root->getTarget());

        $this->assertSame('/', $this->root->getHref());
        $this->assertSame('', $this->root->getNumber());
        $this->assertSame('Example Book', $this->root->getTitle());
        $this->assertSame('Example Book', $this->root->getNumberAndTitle());

        $this->assertTrue($this->root->isRoot());
        $this->assertTrue($this->root->isIndex());
        $this->assertSame($this->root, $this->root->getRoot());

        $this->assertFalse($this->root->hasParent());
        $this->assertNull($this->root->getParent());

        $this->assertSame(array($this->index), $this->root->getChildren());

        $this->assertFalse($this->root->hasPrev());
        $this->assertNull($this->root->getPrev());
        $this->assertTrue($this->root->hasNext());
        $this->assertSame($this->index, $this->root->getNext());

        $this->assertFalse($this->root->hasHeadings());
        $this->assertFalse($this->root->hasTocEntries());
    }

    public function assertCorrectIndex()
    {
        $this->assertInstanceOf('Bookdown\Bookdown\Content\IndexPage', $this->index);

        $this->assertSame(null, $this->index->getOrigin());
        $this->assertSame('/_site/chapter-1/index.html', $this->index->getTarget());
        $this->assertSame('/chapter-1/', $this->index->getHref());

        $this->assertSame('1.', $this->index->getNumber());
        $this->assertSame('Chapter 1', $this->index->getTitle());
        $this->assertSame('1. Chapter 1', $this->index->getNumberAndTitle());

        $this->assertFalse($this->index->isRoot());
        $this->assertTrue($this->index->isIndex());
        $this->assertSame($this->root, $this->index->getRoot());

        $this->assertTrue($this->index->hasParent());
        $this->assertSame($this->root, $this->index->getParent());

        $this->assertSame(array($this->page), $this->index->getChildren());

        $this->assertTrue($this->index->hasPrev());
        $this->assertSame($this->root, $this->index->getPrev());
        $this->assertTrue($this->index->hasNext());
        $this->assertSame($this->page, $this->index->getNext());

        $this->assertFalse($this->index->hasHeadings());
        $this->assertFalse($this->root->hasTocEntries());
    }

    public function assertCorrectPage()
    {
        $this->assertInstanceOf('Bookdown\Bookdown\Content\Page', $this->page);

        $this->assertSame('/path/to/chapter-1/section-1.md', $this->page->getOrigin());
        $this->assertSame('/_site/chapter-1/section-1.html', $this->page->getTarget());

        $this->assertSame('/chapter-1/section-1.html', $this->page->getHref());
        $this->assertSame('1.1.', $this->page->getNumber());
        $this->assertNull($this->page->getTitle());
        $this->assertSame('1.1.', $this->page->getNumberAndTitle());
        $this->page->setTitle('Section 1');
        $this->assertSame('1.1. Section 1', $this->page->getNumberAndTitle());

        $this->assertFalse($this->page->isRoot());
        $this->assertFalse($this->page->isIndex());
        $this->assertSame($this->root, $this->page->getRoot());

        $this->assertTrue($this->page->hasParent());
        $this->assertSame($this->index, $this->page->getParent());

        $this->assertTrue($this->page->hasPrev());
        $this->assertSame($this->index, $this->page->getPrev());
        $this->assertFalse($this->page->hasNext());
        $this->assertNull($this->page->getNext());

        $this->assertFalse($this->page->hasHeadings());
    }
}
