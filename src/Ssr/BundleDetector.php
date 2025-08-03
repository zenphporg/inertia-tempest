<?php

declare(strict_types=1);

namespace Inertia\Ssr;

use Inertia\Configs\InertiaConfig;

use function Tempest\root_path;

final readonly class BundleDetector
{
    public function __construct(
        private InertiaConfig $config,
    ) {}

    public function detect(): ?string
    {
        $potentialPaths = [
            $this->config->ssr->bundle,
            root_path('ssr/inertia.ssr.mjs'),
            root_path('ssr/inertia.ssr.js'),
        ];

        foreach ($potentialPaths as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
