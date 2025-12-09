<?php

namespace SilverStripe\DiscovererElasticEnterprise\Tests\Processors;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\Results\Suggestions;
use SilverStripe\DiscovererElasticEnterprise\Processors\SpellingProcessor;
use Throwable;

class SpellingProcessorTest extends SapphireTest
{

    #[DataProvider('suggestionProvider')]
    public function testGetProcessedSuggestions(array $variation, array $expected, ?string $expectedError): void
    {
        $suggestions = Suggestions::create(200);
        $error = null;

        try {
            SpellingProcessor::singleton()->getProcessedSuggestions($suggestions, $variation);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $suggestionOutput = [];

        foreach ($suggestions->getIterator() as $suggestion) {
            $suggestionOutput[]= $suggestion->getFormatted();
        }

        $this->assertSame($expected, $suggestionOutput);
        $this->assertSame($expectedError, $error);
    }

    public static function suggestionProvider(): array
    {
        return [
            'empty suggest' => [
                [
                    'suggest' => [
                        'title' => [],
                        'body' => [],
                    ],
                ],
                [],
                null,
            ],
            'single suggestion' => [
                // suggestion
                [
                    'suggest' => [
                        'title' => [
                            [
                                'options' => [
                                    ['text' => 'help!'],
                                ],
                            ],
                        ],
                        'body' => [],
                    ],
                ],
                // expected output
                ['help!'],
                // expected error
                null,
            ],
            'duplicate suggestion' => [
                // suggestion
                [
                    'suggest' => [
                        'title' => [
                            [
                                'options' => [
                                    ['text' => 'help!'],
                                ],
                            ],
                        ],
                        'body' => [
                            [
                                'options' => [
                                    ['text' => 'help!'],
                                ],
                            ],
                        ],
                    ],
                ],
                // expected output
                ['help!'],
                // expected error
                null,
            ],
            'multiple suggestions' => [
                // suggestion
                [
                    'suggest' => [
                        'title' => [
                            [
                                'options' => [
                                    ['text' => 'help!'],
                                ],
                            ],
                        ],
                        'body' => [
                            [
                                'options' => [
                                    ['text' => 'me'],
                                ],
                            ],
                        ],
                    ],
                ],
                // expected output
                ['help!', 'me'],
                // expected error
                null,
            ],
            'empty suggest field' => [
                ['suggest' => []], [], 'Missing required top level fields for query suggestions: suggest',
            ],
            'empty response' => [
                [], [], 'Missing required top level fields for query suggestions: suggest',
            ],
        ];
    }

}
