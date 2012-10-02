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
        $prefix = substr($package->getPrettyName(), 0, 16);
        if ('barberry/plugin-' !== $prefix) {
            throw new \InvalidArgumentException(
                'Unable to install plugin, barberry plugins '
                    .'should always start their package name with '
                    .'"barberry/plugin-"'
            );
        }

        return 'plugins/' . substr($package->getPrettyName(), 16);
    }
}
