<?php

namespace App;

use App\DependencyInjection\Compiler\StratigraphicUnitFilterPass;
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
        $container->addCompilerPass(new StratigraphicUnitFilterPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            50 // any priority > 0 works; higher means earlier
        );
    }
}
