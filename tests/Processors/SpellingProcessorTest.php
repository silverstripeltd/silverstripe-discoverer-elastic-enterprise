<?php

namespace SilverStripe\DiscovererElasticEnterprise\Tests\Processors;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\Results\Suggestions;
use SilverStripe\DiscovererElasticEnterprise\Processors\SpellingProcessor;
use Throwable;

class SpellingProcessorTest extends SapphireTest
{

    /**
     * @dataProvider suggestionProvider
     */
    public function testGetProcessedSuggestions(array $variation, array $expected, ?string $expectedError): void
    {
        $suggestions = Suggestions::create();
        $error = null;

        try {
            SpellingProcessor::singleton()->getProcessedSuggestions($suggestions, $variation);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $suggestionOutput = [];

        foreach ($suggestions as $suggestion) {
            $suggestionOutput[]= $suggestion->getRaw();
        }

        $this->assertSame($expected, $suggestionOutput);
        $this->assertSame($expectedError, $error);
    }

    public function suggestionProvider(): array
    {
        return [
            [
                [
                    'suggest' => [
                        'title' => [],
                        'body' => [],
                    ],
                ],
                [],
                null,
            ],
            [
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
            [
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
            [
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
            [
                ['suggest' => []], [], 'Missing required top level fields for query suggestions: suggest',
            ],
            [
                [], [], 'Missing required top level fields for query suggestions: suggest',
            ],
        ];
    }

}
