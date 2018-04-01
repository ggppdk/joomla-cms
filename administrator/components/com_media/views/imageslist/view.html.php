<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * HTML View class for the Media component
 *
 * @since  1.0
 */
class MediaViewImagesList extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   1.0
	 */
	public function display($tpl = null)
	{
		// Do not allow cache
		JFactory::getApplication()->allowCache(false);

		$docs    = $this->get('documents');
		$videos  = $this->get('videos');
		$images  = $this->get('images');
		$folders = $this->get('folders');
		$state   = $this->get('state');

		$this->baseURL = COM_MEDIA_BASEURL;

		$this->docs    = &$docs;
		$this->videos  = &$videos;
		$this->images  = &$images;
		$this->folders = &$folders;

		$this->state   = &$state;

		parent::display($tpl);
	}

	/**
	 * Set the active folder
	 *
	 * @param   integer  $index  Folder position
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setFolder($index = 0)
	{
		if (isset($this->folders[$index]))
		{
			$this->_tmp_folder = &$this->folders[$index];
		}
		else
		{
			$this->_tmp_folder = new JObject;
		}
	}

	/**
	 * Set the active image
	 *
	 * @param   integer  $index  Image position
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setImage($index = 0)
	{
		if (isset($this->images[$index]))
		{
			$this->_tmp_img = &$this->images[$index];
		}
		else
		{
			$this->_tmp_img = new JObject;
		}
	}

	/**
	 * Set the active video
	 *
	 * @param   integer  $index  Video position
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function setVideo($index = 0)
	{
		if (isset($this->videos[$index]))
		{
			$this->_tmp_video = &$this->videos[$index];
		}
		else
		{
			$this->_tmp_video = new JObject;
		}
	}

	/**
	 * Set the active document
	 *
	 * @param   integer  $index  Document position
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function setDocument($index = 0)
	{
		if (isset($this->docs[$index]))
		{
			$this->_tmp_doc = &$this->docs[$index];
		}
		else
		{
			$this->_tmp_doc = new JObject;
		}
	}
}
