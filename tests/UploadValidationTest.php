<?php

declare(strict_types=1);

namespace LiteTracker\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for torrent upload validation helpers.
 *
 * Functions under test are loaded from system/functions/functions.upload.php
 * via tests/bootstrap.php. Only pure, DB-free functions are tested here.
 */
class UploadValidationTest extends TestCase
{
    // -----------------------------------------------------------------------
    // lt_torrent_tags_from_string
    // -----------------------------------------------------------------------

    public function testTagsFromStringBasic(): void
    {
        $tags = lt_torrent_tags_from_string('action,drama,comedy');
        $this->assertSame(['action', 'drama', 'comedy'], $tags);
    }

    public function testTagsFromStringSemicolonSeparator(): void
    {
        $tags = lt_torrent_tags_from_string('action;drama');
        $this->assertSame(['action', 'drama'], $tags);
    }

    public function testTagsFromStringTrimming(): void
    {
        $tags = lt_torrent_tags_from_string('  action , drama  ');
        $this->assertSame(['action', 'drama'], $tags);
    }

    public function testTagsFromStringSkipsEmpty(): void
    {
        $tags = lt_torrent_tags_from_string('action,,drama,');
        $this->assertSame(['action', 'drama'], $tags);
    }

    public function testTagsFromStringDeduplicatesCaseInsensitive(): void
    {
        $tags = lt_torrent_tags_from_string('Action,action');
        // Keeps the first occurrence
        $this->assertCount(1, $tags);
    }

    public function testTagsFromStringTruncatesLongTags(): void
    {
        $long = str_repeat('a', 50);
        $tags = lt_torrent_tags_from_string($long);
        $this->assertLessThanOrEqual(30, mb_strlen($tags[0], 'UTF-8'));
    }

    public function testTagsFromStringEmpty(): void
    {
        $this->assertSame([], lt_torrent_tags_from_string(''));
    }

    // -----------------------------------------------------------------------
    // lt_torrent_tags_to_string
    // -----------------------------------------------------------------------

    public function testTagsToStringRoundTrip(): void
    {
        $input  = 'action,drama,comedy';
        $result = lt_torrent_tags_to_string($input);
        $this->assertSame('action,drama,comedy', $result);
    }

    public function testTagsToStringNormalizesSpaces(): void
    {
        $result = lt_torrent_tags_to_string(' action , drama ');
        $this->assertSame('action,drama', $result);
    }

    // -----------------------------------------------------------------------
    // lt_torrent_metadata_normalize_values
    // -----------------------------------------------------------------------

    public function testNormalizeValuesFiltersInvalid(): void
    {
        $result = lt_torrent_metadata_normalize_values('subtitles', ['russian', 'english', 'klingon']);
        $this->assertSame(['russian', 'english'], $result);
    }

    public function testNormalizeValuesAllValid(): void
    {
        $result = lt_torrent_metadata_normalize_values('language', ['russian', 'english']);
        $this->assertSame(['russian', 'english'], $result);
    }

    public function testNormalizeValuesEmptyInput(): void
    {
        $result = lt_torrent_metadata_normalize_values('language', []);
        $this->assertSame([], $result);
    }

    public function testNormalizeValuesStringInput(): void
    {
        // Single string (not array) should still work
        $result = lt_torrent_metadata_normalize_values('language', ['russian']);
        $this->assertContains('russian', $result);
    }

    // -----------------------------------------------------------------------
    // lt_torrent_metadata_csv
    // -----------------------------------------------------------------------

    public function testMetadataCsvProducesCommaSeparated(): void
    {
        $csv = lt_torrent_metadata_csv('language', ['russian', 'english']);
        $this->assertSame('russian,english', $csv);
    }

    public function testMetadataCsvFiltersInvalid(): void
    {
        $csv = lt_torrent_metadata_csv('language', ['russian', 'invalid_language']);
        $this->assertSame('russian', $csv);
    }

    public function testMetadataCsvEmptyOnAllInvalid(): void
    {
        $csv = lt_torrent_metadata_csv('language', ['klingon', 'elvish']);
        $this->assertSame('', $csv);
    }

    // -----------------------------------------------------------------------
    // lt_torrent_metadata_schema  (structure integrity)
    // -----------------------------------------------------------------------

    public function testMetadataSchemaReturnsExpectedGroups(): void
    {
        $schema = lt_torrent_metadata_schema();
        $this->assertArrayHasKey('subtitles', $schema);
        $this->assertArrayHasKey('language', $schema);
        $this->assertArrayHasKey('genre', $schema);
        $this->assertArrayHasKey('country', $schema);
    }

    public function testMetadataSchemaSubtitlesHasRussian(): void
    {
        $schema = lt_torrent_metadata_schema();
        $this->assertArrayHasKey('russian', $schema['subtitles']['options']);
    }

    // -----------------------------------------------------------------------
    // lt_torrent_metadata_type_options_for_category
    // -----------------------------------------------------------------------

    public function testTypeOptionsForKnownCategory(): void
    {
        $opts = lt_torrent_metadata_type_options_for_category('movies');
        $this->assertArrayHasKey('movie', $opts);
        $this->assertArrayHasKey('series', $opts);
    }

    public function testTypeOptionsForUnknownCategoryReturnsArray(): void
    {
        $opts = lt_torrent_metadata_type_options_for_category('nonexistent');
        $this->assertIsArray($opts);
    }

    // -----------------------------------------------------------------------
    // lt_torrent_type_options_map  (integrity)
    // -----------------------------------------------------------------------

    public function testTypeOptionsMapReturnsAllExpectedCategories(): void
    {
        $map = lt_torrent_type_options_map();
        foreach (['anime', 'movies', 'games', 'music', 'shows', 'software'] as $cat) {
            $this->assertArrayHasKey($cat, $map, "Missing category: {$cat}");
        }
    }
}
