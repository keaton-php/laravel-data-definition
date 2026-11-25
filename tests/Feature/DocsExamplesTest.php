<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Feature;

use Illuminate\Support\Carbon;
use Keaton\DataDefinition\Tests\Support\DocsRunner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The README and the docs (docs/*.md), run as written: every PHP block that is a script is run, and
 * the output block after it is what it prints. The pages are grouped, in order, linking only to
 * pages that exist.
 */
class DocsExamplesTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    /** The nav groups, in the order the sidebar shows them. */
    private const GROUPS = [
        'Start here',
        'Basic usage',
        'Advanced',
        'Reference',
    ];

    /** @return array<string, array{string, string, ?string}> */
    public static function scripts(): array
    {
        $cases = [];

        foreach (DocsRunner::sources() as $name => $markdown) {
            foreach (DocsRunner::blocks($markdown) as $position => $block) {
                if ($block['kind'] === 'script') {
                    $cases["{$name} block {$position}"] = [$name, $block['code'], $block['output']];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('scripts')]
    #[Test]
    public function every_script_runs_and_prints_what_its_page_shows(string $page, string $script, ?string $shown): void
    {
        $this->assertFalse(Carbon::hasTestNow(), 'Carbon test time is set before a docs example runs');

        $ran = DocsRunner::run($script);

        if ($shown !== null) {
            $this->assertSame($shown, trim($ran['output']), "Output of a script in {$page}");
        }

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function the_pages_show_enough_code_to_be_worth_running(): void
    {
        $kinds = array_count_values(array_column(array_merge(...array_values(array_map(DocsRunner::blocks(...), DocsRunner::sources()))), 'kind'));

        $this->assertGreaterThanOrEqual(12, $kinds['script'] ?? 0);
        $this->assertGreaterThanOrEqual(10, $kinds['definition'] ?? 0);
    }

    #[Test]
    public function every_text_block_follows_a_script(): void
    {
        foreach (DocsRunner::sources() as $name => $markdown) {
            $texts = preg_match_all('/^```text$/m', $markdown);
            $outputs = count(array_filter(DocsRunner::blocks($markdown), fn (array $block) => $block['output'] !== null));

            $this->assertSame($texts, $outputs, "{$name} has a text block that does not follow a script");
        }
    }

    #[Test]
    public function every_helper_class_is_used_by_a_page(): void
    {
        $everything = implode("\n", DocsRunner::sources());

        preg_match_all('/^(?:final |abstract )?(?:class|enum|interface) (\w+)/m', (string) file_get_contents(self::ROOT.'/tests/Fixtures/DocsHelpers.php'), $helpers);

        $this->assertCount(2, $helpers[1]);

        foreach ($helpers[1] as $helper) {
            $this->assertStringContainsString($helper, $everything, "{$helper} in tests/Fixtures/DocsHelpers.php is not used by any page");
        }
    }

    #[Test]
    public function the_pages_do_not_point_at_example_files_that_no_longer_exist(): void
    {
        $this->assertStringNotContainsString('docs/examples', implode("\n", DocsRunner::sources()));
        $this->assertDirectoryDoesNotExist(self::ROOT.'/docs/examples');
    }

    #[Test]
    public function the_pages_are_grouped_in_sidebar_order_and_start_with_their_group(): void
    {
        $seen = [];

        foreach (self::pages() as $name => $text) {
            $this->assertMatchesRegularExpression('/\A<!-- nav-group: (.+) -->\n# \S/', $text, "{$name} must start with its nav group and a title");

            if (preg_match('/\A<!-- nav-group: (.+) -->/', $text, $group) !== 1) {
                $this->fail("{$name} has no nav group");
            }

            $seen[] = $group[1];
        }

        $positions = array_map(fn (string $group) => array_search($group, self::GROUPS, true), $seen);
        $inOrder = $positions;
        sort($inOrder);

        $this->assertNotContains(false, $positions, 'A page names a nav group that is not in the list');
        $this->assertSame($inOrder, $positions, 'The pages are not in sidebar order');
        $this->assertSame(self::GROUPS, array_values(array_unique($seen)), 'A nav group has no page');
    }

    #[Test]
    public function every_page_has_two_headings_and_ends_with_a_next_link_except_the_last(): void
    {
        $pages = self::pages();
        $last = array_key_last($pages);

        foreach ($pages as $name => $text) {
            $this->assertGreaterThanOrEqual(2, preg_match_all('/^## /m', $text), "{$name} needs at least two H2 headings");

            if ($name !== $last) {
                $this->assertMatchesRegularExpression('/\n\*\*Next:\*\* \[[^\]]+\]\(\S+\.md\)\.\n\z/', $text, "{$name} must end with a Next link");
            }
        }
    }

    #[Test]
    public function the_docs_stay_a_few_short_flat_pages(): void
    {
        $pages = self::pages();

        $this->assertGreaterThanOrEqual(6, count($pages));
        $this->assertLessThanOrEqual(8, count($pages));
        $this->assertLessThanOrEqual(720, array_sum(array_map(fn (string $text) => substr_count($text, "\n"), $pages)), 'The docs have grown past about 720 lines in total');

        foreach ($pages as $name => $text) {
            $this->assertLessThanOrEqual(120, substr_count($text, "\n"), "{$name} is longer than about 120 lines");
            $this->assertMatchesRegularExpression('/\A(index|\d\d-[a-z-]+)\.md\z/', $name, 'Pages are flat: index.md, then NN-slug.md');
        }

        $this->assertSame([], glob(self::ROOT.'/docs/*', GLOB_ONLYDIR), 'The docs folder has a sub-folder');
    }

    #[Test]
    public function every_link_to_a_page_points_at_a_page_that_exists(): void
    {
        preg_match_all('/\]\(((?:\.\/)?[0-9a-z-]+\.md)\)/', self::docs(), $links);

        $this->assertNotEmpty($links[1]);

        foreach ($links[1] as $link) {
            $this->assertFileExists(self::ROOT.'/docs/'.ltrim($link, './'), "Broken docs link {$link}");
        }
    }

    #[Test]
    public function the_navigation_links_that_matter_point_at_the_intended_page(): void
    {
        $pages = self::pages();

        $this->assertStringContainsString('(02-installation.md)', $pages['index.md']);
        $this->assertStringContainsString('(06-custom-casts.md)', $pages['05-casting.md']);
        $this->assertStringContainsString('(08-reference.md)', $pages['07-nesting-and-collections.md']);
    }

    /** @return array<string, string> page file name => its text, in file-name order (index first) */
    private static function pages(): array
    {
        $files = glob(self::ROOT.'/docs/*.md') ?: [];

        // The introduction, index.md, is always first; the numbered pages follow in order.
        usort($files, fn (string $a, string $b) => [basename($a) !== 'index.md', basename($a)] <=> [basename($b) !== 'index.md', basename($b)]);

        $pages = [];

        foreach ($files as $file) {
            $pages[basename($file)] = (string) file_get_contents($file);
        }

        return $pages;
    }

    private static function docs(): string
    {
        return implode("\n", self::pages());
    }
}
