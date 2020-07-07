<?php
namespace Cms\Test\TestCase\View;

use Cms\View\Shortcode;
use PHPUnit\Framework\TestCase;

/**
 * Cms\Model\Table\ArticlesTable Test Case
 */
class ShortcodeTest extends TestCase
{
    public function testGetPlainShortcode(): void
    {
        $content = 'A plain [foo] shortcode.';
        $expected = [
            ['full' => '[foo]', 'name' => 'foo', 'params' => [], 'content' => ''],
        ];

        $this->assertEquals($expected, Shortcode::get($content));
    }

    public function testGetPlainShortcodeWithClosingTag(): void
    {
        $content = 'A plain [foo][/foo] shortcode.';
        $expected = [
            ['full' => '[foo][/foo]', 'name' => 'foo', 'params' => [], 'content' => ''],
        ];

        $this->assertEquals($expected, Shortcode::get($content));
    }

    public function testGetPlainShortcodeWithContent(): void
    {
        $content = 'A plain [foo]Hello World![/foo] shortcode with content.';
        $expected = [
            [
                'full' => '[foo]Hello World![/foo]',
                'name' => 'foo',
                'params' => [],
                'content' => 'Hello World!',
            ],
        ];

        $this->assertEquals($expected, Shortcode::get($content));
    }

    public function testGetShortcodeWithParams(): void
    {
        $content = 'Shortcode [foo hello="world" world=\'hello\'] with parameters.';
        $expected = [
            [
                'full' => '[foo hello="world" world=\'hello\']',
                'name' => 'foo',
                'params' => ['hello' => 'world', 'world' => 'hello'],
                'content' => '',
            ],
        ];

        $this->assertEquals($expected, Shortcode::get($content));
    }

    public function testGetShortcodeWithParamsAndClosingTag(): void
    {
        $content = 'Shortcode [foo hello="world" world=\'hello\'][/foo] with parameters.';
        $expected = [
            [
                'full' => '[foo hello="world" world=\'hello\'][/foo]',
                'name' => 'foo',
                'params' => ['hello' => 'world', 'world' => 'hello'],
                'content' => '',
            ],
        ];

        $this->assertEquals($expected, Shortcode::get($content));
    }

    public function testGetShortcodeWithParamsAndContent(): void
    {
        $content = 'Shortcode [foo hello="world" world=\'hello\']Hello World![/foo] with parameters.';
        $expected = [
            [
                'full' => '[foo hello="world" world=\'hello\']Hello World![/foo]',
                'name' => 'foo',
                'params' => ['hello' => 'world', 'world' => 'hello'],
                'content' => 'Hello World!',
            ],
        ];

        $this->assertEquals($expected, Shortcode::get($content));
    }

    public function testGetWithEmptyContent(): void
    {
        $content = '';
        $expected = [];

        $this->assertEquals($expected, Shortcode::get($content));
    }

    /**
     * @dataProvider invalidContentProvider
     * @param mixed $content Invalid content
     * @param mixed[] $expected Expected result
     */
    public function testGetWithInvalidContent($content, array $expected): void
    {
        $this->assertEquals($expected, Shortcode::get($content));
    }

    /**
     * @return mixed[]
     */
    public function invalidContentProvider(): array
    {
        return [
            [357, []],
            [true, []],
        ];
    }

    public function testParseNonExistingShortcode(): void
    {
        $content = 'A non-parsable [foo] shortcode.';
        $shortcodes = Shortcode::get($content);
        $expected = '';

        foreach ($shortcodes as $shortcode) {
            $this->assertEquals($expected, Shortcode::parse($shortcode));
        }
    }

    public function testParseEmptyShortcode(): void
    {
        $shortcode = [];
        $expected = '';

        $this->assertEquals($expected, Shortcode::parse($shortcode));
    }

    public function testParseGalleryWithoutPath(): void
    {
        $content = 'A [gallery] shortcode.';
        $shortcodes = Shortcode::get($content);
        $expected = '<div class="alert alert-danger" role="alert">Gallery has no path</div>';

        foreach ($shortcodes as $shortcode) {
            $this->assertEquals($expected, Shortcode::parse($shortcode));
        }
    }

    public function testParseGalleryWithInvalidPath(): void
    {
        $content = 'A [gallery path="/some/random/path"] shortcode with invalid path.';
        $shortcodes = Shortcode::get($content);
        $expected = '<div class="alert alert-danger" role="alert">No images found in: some/random/path</div>';

        foreach ($shortcodes as $shortcode) {
            $this->assertEquals($expected, Shortcode::parse($shortcode));
        }
    }

    public function testParseGalleryWithoutImages(): void
    {
        // current dir as path
        $content = 'A [gallery path="js"] shortcode without images.';
        $shortcodes = Shortcode::get($content);
        $expected = '<div class="row"></div>';

        foreach ($shortcodes as $shortcode) {
            $this->assertEquals($expected, Shortcode::parse($shortcode));
        }
    }

    public function testDoShortcode(): void
    {
        $content = 'A plain [foo] shortcode.';
        $expected = 'A plain  shortcode.';

        $this->assertEquals($expected, Shortcode::doShortcode($content));
    }

    public function testDoShortcodeInvalid(): void
    {
        $content = '';
        $expected = '';

        $this->assertEquals($expected, Shortcode::doShortcode($content));
    }

    public function testDoShortcodeWithParams(): void
    {
        $content = 'Shortcode [foo hello="world" world=\'hello\'] with parameters.';
        $expected = 'Shortcode  with parameters.';

        $this->assertEquals($expected, Shortcode::doShortcode($content));
    }

    public function testDoShortcodeWithContent(): void
    {
        $content = 'A plain [foo]Hello World![/foo] shortcode with content.';
        $expected = 'A plain Hello World! shortcode with content.';

        $this->assertEquals($expected, Shortcode::doShortcode($content));
    }

    public function testDoShortcodeWithParamsAndContent(): void
    {
        $content = 'Shortcode [foo hello="world" world=\'hello\']Hello World![/foo] with parameters.';
        $expected = 'Shortcode Hello World! with parameters.';

        $this->assertEquals($expected, Shortcode::doShortcode($content));
    }
}
