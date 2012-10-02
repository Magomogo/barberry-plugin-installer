<?php
namespace Barberry\Plugin;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class Installer extends LibraryInstaller
{
    public function supports($packageType)
    {
        return 'barberry-plugin' === $packageType;
    }

    public function getInstallPath(PackageInterface $package)
    {
        $prefix = substr($package->getPrettyName(), 0, 23);
        if ('barberry/plugin-' !== $prefix) {
            throw new \InvalidArgumentException(
                'Unable to install template, phpdocumentor templates '
                    .'should always start their package name with '
                    .'"phpdocumentor/template-"'
            );
        }

        return 'plugins/'.substr($package->getPrettyName(), 16);
    }
}
