<?php
namespace Barberry\Plugin;

use Barberry\Direction\Composer as DirectionComposer;
use Barberry\Monitor\Composer as MonitorComposer;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Autoload\AutoloadGenerator;

class ComposerInstaller extends LibraryInstaller
{
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $pluginName = self::assertBarberryPlugin($package);
        parent::install($repo, $package);

        $this->registerAutoloader($package);
        $this->installPlugin($package, $pluginName);
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $pluginName = self::assertBarberryPlugin($target);
        parent::update($repo, $initial, $target);

        $this->registerAutoloader($target);
        $this->installPlugin($target, $pluginName);
    }

//----------------------------------------------------------------------------------------------------------------------

    private function registerAutoloader($package)
    {
        $generator = new AutoloadGenerator(new EventDispatcher($this->composer, $this->io));
        $map = $generator->parseAutoloads(
            array(array($package, $this->getInstallPath($package))),
            new Package('dummy', '1.0.0.0', '1.0.0')
        );
        $classLoader = $generator->createLoader($map);
        $classLoader->register();
    }

    private function installPlugin($package, $pluginName)
    {
        $extra = $this->composer->getPackage()->getExtra();

        $tempPath = rtrim(
            array_key_exists('barberry_plugins_temp_path', $extra) ?
                $extra['barberry_plugins_temp_path'] : $this->getInstallPath($package) . '/tmp',
            '/'
        );

        $this->filesystem->ensureDirectoryExists($tempPath);

        $directionPath = $this->vendorDir . '/../barberry-directions';
        $this->filesystem->ensureDirectoryExists($directionPath);

        $monitorPath = $this->vendorDir . '/../barberry-monitors';
        $this->filesystem->ensureDirectoryExists($monitorPath);

        $installerClassName = '\\Barberry\\Plugin\\' . $pluginName . '\\Installer';
        $installer = new $installerClassName();
        $installer->install(
            new DirectionComposer($directionPath . '/', $tempPath . '/'),
            new MonitorComposer($monitorPath . '/', $tempPath . '/'),
            self::readPluginParameters($extra, $package->getPrettyName())
        );
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

    private static function readPluginParameters(array $extra, $pluginPackageName)
    {
        return array_key_exists($pluginPackageName, $extra) ? $extra[$pluginPackageName] : array();
    }

}
