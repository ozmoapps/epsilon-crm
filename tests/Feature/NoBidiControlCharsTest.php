<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class NoBidiControlCharsTest extends TestCase
{
    public function test_source_files_do_not_contain_bidi_control_chars(): void
    {
        $pattern = '/[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200E}\x{200F}]/u';
        $paths = [
            base_path('app'),
            base_path('resources/views'),
            base_path('routes'),
            base_path('tests'),
        ];

        $violations = [];

        foreach ($paths as $path) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if ($contents !== false && preg_match($pattern, $contents)) {
                    $violations[] = $file->getPathname();
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Bidi control characters found in:\n" . implode("\n", $violations)
        );
    }
}
