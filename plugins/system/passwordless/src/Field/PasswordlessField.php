<?php
/**
 * @package   AkeebaPasswordlessLogin
 * @copyright Copyright (c)2018-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\Passwordless\Field;

// Prevent direct access
defined('_JEXEC') or die;

use Akeeba\Passwordless\Helper\Joomla;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;


class PasswordlessField extends FormField
{
	/**
	 * Element name
	 *
	 * @var   string
	 */
	protected $_name = 'Passwordless';

	function getInput()
	{
		$user_id = $this->form->getData()->get('id', null);

		if (is_null($user_id))
		{
			return Text::_('PLG_SYSTEM_PASSWORDLESS_ERR_NOUSER');
		}

		Text::script('PLG_SYSTEM_PASSWORDLESS_ERR_NO_BROWSER_SUPPORT', true);
		Text::script('PLG_SYSTEM_PASSWORDLESS_MANAGE_BTN_SAVE_LABEL', true);
		Text::script('PLG_SYSTEM_PASSWORDLESS_MANAGE_BTN_CANCEL_LABEL', true);
		Text::script('PLG_SYSTEM_PASSWORDLESS_MSG_SAVED_LABEL', true);
		Text::script('PLG_SYSTEM_PASSWORDLESS_ERR_LABEL_NOT_SAVED', true);
		Text::script('PLG_SYSTEM_PASSWORDLESS_ERR_NOT_DELETED', true);

		$credentialRepository = new \Joomla\Plugin\System\Passwordless\Credential\CredentialsRepository();

		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$wam = $app->getDocument()->getWebAssetManager();
		$wam->getRegistry()->addExtensionRegistryFile('plg_system_passwordless');
		$wam->usePreset('plg_system_passwordless.manage');

		$layoutFile  = new FileLayout('akeeba.passwordless.manage', JPATH_PLUGINS . '/system/passwordless/layout');
		$currentUser = Factory::getApplication()->getIdentity() ?? new User();

		return $layoutFile->render([
			'user'        => Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id),
			'allow_add'   => $user_id == $currentUser->id,
			'credentials' => $credentialRepository->getAll($user_id),
		]);
	}
}
