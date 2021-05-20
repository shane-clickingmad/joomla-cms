<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Service
 *
 * @copyright   (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Service\Provider;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\ApiApplication;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Console\CheckJoomlaUpdatesCommand;
use Joomla\CMS\Console\ExtensionInstallCommand;
use Joomla\CMS\Console\ExtensionDiscoverInstallCommand;
use Joomla\CMS\Console\ExtensionRemoveCommand;
use Joomla\CMS\Console\ExtensionsListCommand;
use Joomla\CMS\Console\FinderIndexCommand;
use Joomla\CMS\Console\GetConfigurationCommand;
use Joomla\CMS\Console\Loader\WritableContainerLoader;
use Joomla\CMS\Console\Loader\WritableLoaderInterface;
use Joomla\CMS\Console\SessionGcCommand;
use Joomla\CMS\Console\SessionMetadataGcCommand;
use Joomla\CMS\Console\SetConfigurationCommand;
use Joomla\CMS\Console\SiteDownCommand;
use Joomla\CMS\Console\SiteUpCommand;
use Joomla\CMS\Console\UpdateCoreCommand;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Console\Application as BaseConsoleApplication;
use Joomla\Console\Loader\LoaderInterface;
use Joomla\Database\Command\ExportCommand;
use Joomla\Database\Command\ImportCommand;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Session\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Application service provider
 *
 * @since  4.0.0
 */
class Application implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		$container->alias(AdministratorApplication::class, 'JApplicationAdministrator')
			->share(
				'JApplicationAdministrator',
				function (Container $container)
				{
					$app = new AdministratorApplication(null, $container->get('config'), null, $container);

					// The session service provider needs Factory::$application, set it if still null
					if (Factory::$application === null)
					{
						Factory::$application = $app;
					}

					$app->setDispatcher($container->get(DispatcherInterface::class));
					$app->setLogger($container->get(LoggerInterface::class));
					$app->setSession($container->get(SessionInterface::class));
					$app->setUserFactory($container->get(UserFactoryInterface::class));

					return $app;
				},
				true
			);

		$container->alias(SiteApplication::class, 'JApplicationSite')
			->share(
				'JApplicationSite',
				function (Container $container)
				{
					$app = new SiteApplication(null, $container->get('config'), null, $container);

					// The session service provider needs Factory::$application, set it if still null
					if (Factory::$application === null)
					{
						Factory::$application = $app;
					}

					$app->setDispatcher($container->get(DispatcherInterface::class));
					$app->setLogger($container->get(LoggerInterface::class));
					$app->setSession($container->get(SessionInterface::class));
					$app->setUserFactory($container->get(UserFactoryInterface::class));

					return $app;
				},
				true
			);

		$container->alias(ConsoleApplication::class, BaseConsoleApplication::class)
			->share(
				BaseConsoleApplication::class,
				function (Container $container)
				{
					$dispatcher = $container->get(DispatcherInterface::class);

					// Console uses the default system language
					$config = $container->get('config');
					$locale = $config->get('language');
					$debug  = $config->get('debug_lang');

					$lang = $container->get(LanguageFactoryInterface::class)->createLanguage($locale, $debug);

					$app = new ConsoleApplication($config, $dispatcher, $container, $lang);

					// The session service provider needs Factory::$application, set it if still null
					if (Factory::$application === null)
					{
						Factory::$application = $app;
					}

					$app->setCommandLoader($container->get(LoaderInterface::class));
					$app->setLogger($container->get(LoggerInterface::class));
					$app->setSession($container->get(SessionInterface::class));
					$app->setUserFactory($container->get(UserFactoryInterface::class));

					return $app;
				},
				true
			);

		$container->alias(WritableContainerLoader::class, LoaderInterface::class)
			->alias(WritableLoaderInterface::class, LoaderInterface::class)
			->share(
				LoaderInterface::class,
				function (Container $container)
				{
					$mapping = [
						SessionGcCommand::getDefaultName()                 => SessionGcCommand::class,
						SessionMetadataGcCommand::getDefaultName()         => SessionMetadataGcCommand::class,
						ExportCommand::getDefaultName()                    => ExportCommand::class,
						ImportCommand::getDefaultName()                    => ImportCommand::class,
						SiteDownCommand::getDefaultName()                  => SiteDownCommand::class,
						SiteUpCommand::getDefaultName()                    => SiteUpCommand::class,
						SetConfigurationCommand::getDefaultName()          => SetConfigurationCommand::class,
						GetConfigurationCommand::getDefaultName()          => GetConfigurationCommand::class,
						ExtensionsListCommand::getDefaultName()            => ExtensionsListCommand::class,
						CheckJoomlaUpdatesCommand::getDefaultName()        => CheckJoomlaUpdatesCommand::class,
						ExtensionRemoveCommand::getDefaultName()           => ExtensionRemoveCommand::class,
						ExtensionInstallCommand::getDefaultName()          => ExtensionInstallCommand::class,
						ExtensionDiscoverInstallCommand::getDefaultName()  => ExtensionDiscoverInstallCommand::class,
						UpdateCoreCommand::getDefaultName()                => UpdateCoreCommand::class,
						FinderIndexCommand::getDefaultName()               => FinderIndexCommand::class,
					];

					return new WritableContainerLoader($container, $mapping);
				},
				true
			);

		$container->alias(ApiApplication::class, 'JApplicationApi')
			->share(
				'JApplicationApi',
				function (Container $container) {
					$app = new ApiApplication(null, $container->get('config'), null, $container);

					// The session service provider needs Factory::$application, set it if still null
					if (Factory::$application === null)
					{
						Factory::$application = $app;
					}

					$app->setDispatcher($container->get('Joomla\Event\DispatcherInterface'));
					$app->setLogger($container->get(LoggerInterface::class));
					$app->setSession($container->get('Joomla\Session\SessionInterface'));

					return $app;
				},
				true
			);
	}
}