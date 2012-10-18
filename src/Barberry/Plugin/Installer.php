<?php
namespace Barberry\Plugin;
use Barberry\Direction\Composer as DirectionComposer;
use Barberry\Monitor\Composer as MonitorComposer;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Autoload\AutoloadGenerator;
use Composer\IO\IOInterface;
use Composer\Composer;

class Installer extends LibraryInstaller
{
    public function __construct(IOInterface $io, Composer $composer, $type = 'library')
    {
        parent::__construct($io, $composer, 'composer-installer');
        $this->registerBarberryInterfacesAutoloader($composer);
    }

    public function supports($packageType)
    {
        return 'barberry-plugin' === $packageType;
    }

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $pluginName = self::assertBarberryPlugin($package);
        parent::install($repo, $package);

        $this->registerAutoloader($package);
        $this->installDirections($package, $pluginName);
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $pluginName = self::assertBarberryPlugin($target);
        parent::update($repo, $initial, $target);

        $this->registerAutoloader($target);
        $this->installDirections($target, $pluginName);
    }

    private function registerBarberryInterfacesAutoloader($composer)
    {
        foreach ($composer->getRepositoryManager()->getLocalRepositories() as $repo) {
            foreach ($repo->getPackages() as $package) {
                if ($package->getName() === 'barberry/interfaces') {
                    $this->registerAutoloader($package);
                }
            }
        }
    }

    private function registerAutoloader($package)
    {
        $generator = new AutoloadGenerator;
        $map = $generator->parseAutoloads(array(array($package, $this->getInstallPath($package))));
        $classLoader = $generator->createLoader($map);
        $classLoader->register();
    }

    private function installDirections($package, $pluginName)
    {
        $tempPath = $this->getInstallPath($package) . '/tmp';
        $this->filesystem->ensureDirectoryExists($tempPath);

        $directionPath = $this->vendorDir . '/../barberry-directions';
        $this->filesystem->ensureDirectoryExists($directionPath);

        $monitorPath = $this->vendorDir . '/../barberry-monitors';
        $this->filesystem->ensureDirectoryExists($monitorPath);

        $installerClassName = '\\Barberry\\Plugin\\' . $pluginName . '\\Installer';
        $installer = new $installerClassName($tempPath . '/');
        $installer->install(new DirectionComposer($directionPath . '/'), new MonitorComposer($monitorPath . '/'));
    }

    private static function assertBarberryPlugin(PackageInterface $package)
    {
        $prefix = substr($package->getPrettyName(), 0, 16);
        if ('barberry/plugin-' !== $prefix) {
            throw new \InvalidArgumentException(
                'Unable to install plugin, barberry plugins '
                    .'should always start their package name with '
                    .'"barberry/plugin-"'
            );
        }

        return ucfirst(substr($package->getPrettyName(), 16));
    }

}
