<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Fluent;
use Keaton\DataDefinition\DataDefinition;
use Keaton\DataDefinition\Exceptions\CastFailed;
use Keaton\DataDefinition\Tests\Support\DocsRunner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The code examples in README.md, run as written.
 *
 * The README opens with a before and an after: the payload read by hand, then the same payload
 * read through a definition, each with the output it prints. One more example follows.
 * DocsExamplesTest runs the scripts and checks their output; this class checks the shape of the
 * README itself. Every other example lives on a docs page and is checked there too.
 */
class ReadmeExamplesTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    #[Test]
    public function the_readme_opens_with_a_before_and_an_after_and_the_output_of_each(): void
    {
        $blocks = DocsRunner::blocks($this->readme());

        $this->assertSame(['script', 'definition', 'script', 'script'], array_column($blocks, 'kind'));
        $this->assertStringNotContainsString('Lead::', $blocks[0]['code'], 'The before does not use the package');
        $this->assertStringContainsString('Lead::make($payload)', $blocks[2]['code']);

        foreach ([0, 2, 3] as $script) {
            $this->assertNotNull($blocks[$script]['output']);
        }
    }

    #[Test]
    public function the_before_and_the_after_read_the_same_payload(): void
    {
        $blocks = DocsRunner::blocks($this->readme());

        $before = DocsRunner::run($blocks[0]['code'])['vars'];
        $after = DocsRunner::run($blocks[2]['code'])['vars'];

        $this->assertSame($before['payload'], $after['payload']);
        $this->assertSame('n/a', $before['payload']['contact']['fields']['monthly_budget']);
        $this->assertSame(0, $before['budget'], 'The hand-written cast turns "n/a" into 0');
    }

    #[Test]
    public function the_after_reads_the_values_the_readme_says_it_does(): void
    {
        $lead = DocsRunner::run(DocsRunner::blocks($this->readme())[2]['code'])['vars']['lead'];

        $this->assertInstanceOf(DataDefinition::class, $lead);
        $this->assertSame('Lead', $lead::class);
        $this->assertSame('ana@example.com', $lead->email);
        $this->assertSame('google', $lead->source);
        $this->assertInstanceOf(Carbon::class, $lead->at);

        $this->expectException(CastFailed::class);

        $budget = $lead->budget;
    }

    #[Test]
    public function the_second_example_reads_a_typed_value_and_a_changed_copy(): void
    {
        $vars = DocsRunner::run(DocsRunner::blocks($this->readme())[3]['code'])['vars'];

        $this->assertSame(2500, $vars['lead']->budget);
        $this->assertSame(4000, $vars['raised']->budget);
        $this->assertInstanceOf(Carbon::class, $vars['lead']->at);
    }

    #[Test]
    public function the_readme_sends_readers_to_a_docs_page_that_exists(): void
    {
        $this->assertStringContainsString('https://kevineaton.dev/docs/laravel-data-definition/basic-usage/', $this->readme());

        // A docs page's address is its file name without the NN- order prefix.
        $this->assertNotEmpty(glob(self::ROOT.'/docs/*-basic-usage.md'));
    }

    #[Test]
    public function the_readme_stays_short(): void
    {
        $lines = substr_count($this->readme(), "\n");

        $this->assertGreaterThanOrEqual(90, $lines);
        $this->assertLessThanOrEqual(200, $lines);
    }

    #[Test]
    public function the_tests_badge_names_the_workflow_that_runs_the_tests(): void
    {
        $this->assertStringContainsString('/laravel-data-definition/tests.yml?branch=1.x', $this->readme());
        $this->assertStringNotContainsString('branch=main', $this->readme());
        $this->assertFileExists(self::ROOT.'/.github/workflows/tests.yml');
    }

    #[Test]
    public function every_relative_link_in_the_readme_points_at_a_file_in_the_repo(): void
    {
        preg_match_all('/\]\((?!https?:)([^)#]+)\)/', $this->readme(), $links);

        foreach (['CHANGELOG.md', 'CONTRIBUTING.md', 'SECURITY.md', 'LICENSE.md'] as $footer) {
            $this->assertContains($footer, $links[1], "The README does not link to {$footer}");
        }

        foreach ($links[1] as $link) {
            $this->assertFileExists(self::ROOT.'/'.$link, "The README links to {$link}, which is not in the repo");
        }
    }

    #[Test]
    public function what_the_readme_says_about_fluent_is_true_of_the_installed_laravel(): void
    {
        $fluent = new Fluent(['contact' => ['budget' => 'n/a']]);

        $this->assertSame('n/a', $fluent->get('contact.budget'), 'Fluent reads dot-paths');

        $writable = new Fluent(['source' => 'bing']);
        $writable['source'] = 'google';
        $this->assertSame('google', $writable->get('source'), 'Fluent is writable');

        // The typed getters arrived in Laravel 11.34, and the package also runs on 11.33.2.
        $typed = new ReflectionClass(Fluent::class);

        if ($typed->hasMethod('integer')) {
            $this->assertSame(0, $fluent->integer('contact.budget'));
        }

        if ($typed->hasMethod('date')) {
            $at = (new Fluent(['at' => '2026-03-02T09:15:00Z']))->date('at');

            $this->assertInstanceOf(Carbon::class, $at);
            $this->assertSame('2 Mar 2026', $at->format('j M Y'));
        }
    }

    private function readme(): string
    {
        return (string) file_get_contents(self::ROOT.'/README.md');
    }
}
