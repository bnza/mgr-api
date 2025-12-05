<?php

namespace App;

use App\DependencyInjection\Compiler\AnalysisFiltersCompilerPass;
use App\DependencyInjection\Compiler\MediaObjectFiltersCompilerPass;
use App\DependencyInjection\Compiler\StratigraphicUnitFiltersCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        foreach ([
            AnalysisFiltersCompilerPass::class,
            MediaObjectFiltersCompilerPass::class,
            StratigraphicUnitFiltersCompilerPass::class,
        ] as $className) {
            $container->addCompilerPass(new $className(),
                PassConfig::TYPE_BEFORE_OPTIMIZATION,
                50 // any priority > 0 works; higher means earlier
            );
        }
    }
}
