<?php
/**
 * @copyright	Copyright (C) 2011 Thibaut Cuvelier. All rights reserved.
 */

defined('JPATH_BASE') or die;
jimport('joomla.utilities.date');

/**
 * IEEE custom fields. 
 */
class plgUserIEEEProfile extends JPlugin
{
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	function onContentPrepareData($context, $data)
	{
		if (!in_array($context, array('com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile')))
		{
			return true;
		}

		if (is_object($data))
		{
			$userId = isset($data->id) ? $data->id : 0;

			if (!isset($data->ieeeProfile) and $userId > 0) {

				// Load the IEEE profile data from the database.
				$db = JFactory::getDbo();
				$db->setQuery(
					'SELECT profile_key, profile_value FROM #__user_ieee_profiles' .
					' WHERE user_id = '.(int) $userId." AND profile_key LIKE 'ieee_profile.%'" .
					' ORDER BY ordering'
				);
				$results = $db->loadRowList();

				// Check for a database error.
				if ($db->getErrorNum())
				{
					$this->_subject->setError($db->getErrorMsg());
					return false;
				}

				// Merge the profile data.
				$data->ieeeProfile = array();

				foreach ($results as $v)
				{
					$k = str_replace('ieeeProfile.', '', $v[0]);
					$data->ieeeProfile[$k] = $v[1];
				}
			}
		}

		return true;
	}

	function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}

		// Check we are manipulating a valid form.
		if (!in_array($form->getName(), array('com_admin.profile','com_users.user', 'com_users.registration','com_users.profile')))
		{
			return true;
		}

		// Add the registration fields to the form.
		JForm::addFormPath(dirname(__FILE__).'/profiles');
		
		if($form->getName() == 'com_admin.profile')
			$form->loadFile('profile_back', false);
		else
			$form->loadFile('profile_front', false);

		return true;
	}

	function onUserAfterSave($data, $isNew, $result, $error)
	{
		$userId	= JArrayHelper::getValue($data, 'id', 0, 'int');

		if ($userId && $result && isset($data['ieeeProfile']) && (count($data['ieeeProfile'])))
		{
			try
			{
				$db = JFactory::getDbo();
				$db->setQuery(
					'DELETE FROM #__user_ieee_profiles WHERE user_id = '.$userId .
					" AND profile_key LIKE 'profile.%'"
				);

				if (!$db->query()) 
					throw new Exception($db->getErrorMsg());

				$tuples = array();
				$order	= 1;

				foreach ($data['ieeeProfile'] as $k => $v)
					$tuples[] = '('.$userId.', '.$db->quote('ieeeProfile.'.$k).', '.$db->quote($v).', '.$order++.')'; 

				$db->setQuery('INSERT INTO #__user_ieee_profiles VALUES '.implode(', ', $tuples));

				if (!$db->query()) 
					throw new Exception($db->getErrorMsg());

			}
			catch (JException $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}

		return true;
	}

	function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success) {
			return false;
		}

		$userId	= JArrayHelper::getValue($user, 'id', 0, 'int');

		if ($userId)
		{
			try
			{
				$db = JFactory::getDbo();
				$db->setQuery(
					'DELETE FROM #__user_ieee_profiles WHERE user_id = '.$userId .
					" AND profile_key LIKE 'profile.%'"
				);

				if (!$db->query()) 
					throw new Exception($db->getErrorMsg());
			}
			catch (JException $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}

		return true;
	}
}
