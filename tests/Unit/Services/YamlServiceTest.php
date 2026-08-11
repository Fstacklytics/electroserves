<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\YamlService;
use Tests\TestCase;

class YamlServiceTest extends TestCase
{
    private YamlService $yaml;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yaml = app(YamlService::class);
    }

    public function test_it_parses_valid_yaml(): void
    {
        $this->assertSame(
            ['site_name' => 'ElectroServes', 'phone' => '+255'],
            $this->yaml->parse("site_name: ElectroServes\nphone: '+255'"),
        );
    }

    public function test_empty_yaml_yields_an_empty_array(): void
    {
        $this->assertSame([], $this->yaml->parse(''));
    }

    public function test_malformed_yaml_returns_null_rather_than_throwing(): void
    {
        $this->assertNull($this->yaml->parse("key: \"unterminated\n  other: [1,"));
    }

    public function test_yaml_that_is_not_a_mapping_returns_null(): void
    {
        $this->assertNull($this->yaml->parse('just a scalar string'));
    }

    public function test_a_missing_file_returns_null(): void
    {
        $this->assertNull($this->yaml->parseFile('/no/such/path/site.yml'));
    }

    public function test_it_splits_frontmatter_from_the_body(): void
    {
        $result = $this->yaml->parseFrontMatter(<<<'MD'
        ---
        title: A Post
        published: true
        ---

        The body text.
        MD);

        $this->assertSame('A Post', $result['attributes']['title']);
        $this->assertTrue($result['attributes']['published']);
        $this->assertSame('The body text.', $result['body']);
    }

    public function test_a_document_without_frontmatter_is_all_body(): void
    {
        $result = $this->yaml->parseFrontMatter('Just body text, no frontmatter.');

        $this->assertSame([], $result['attributes']);
        $this->assertSame('Just body text, no frontmatter.', $result['body']);
    }

    public function test_malformed_frontmatter_returns_null(): void
    {
        $this->assertNull($this->yaml->parseFrontMatter("---\ntitle: \"unterminated\n  bad: [\n---\n\nBody"));
    }

    public function test_crlf_line_endings_are_handled(): void
    {
        $result = $this->yaml->parseFrontMatter("---\r\ntitle: Windows File\r\n---\r\n\r\nBody text.");

        $this->assertSame('Windows File', $result['attributes']['title']);
        $this->assertSame('Body text.', $result['body']);
    }

    public function test_a_utf8_bom_does_not_prevent_frontmatter_parsing(): void
    {
        $result = $this->yaml->parseFrontMatter("\xEF\xBB\xBF---\ntitle: BOM File\n---\n\nBody.");

        $this->assertSame('BOM File', $result['attributes']['title']);
    }

    public function test_frontmatter_with_an_empty_body_is_valid(): void
    {
        $result = $this->yaml->parseFrontMatter("---\ntitle: No Body\n---\n");

        $this->assertSame('No Body', $result['attributes']['title']);
        $this->assertSame('', $result['body']);
    }
}
