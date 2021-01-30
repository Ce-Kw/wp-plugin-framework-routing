<?php

namespace CEKW\WpPluginFramework\Routing;

interface ControllerInterface
{
    public function setSeoHelper(SeoHelper $seoHelper): void;
}