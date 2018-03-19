<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Component\Router\Rules;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterView;

/**
 * Rule to identify the right Itemid for a view in a component
 *
 * @since  3.4
 */
class MenuRules implements RulesInterface
{
	/**
	 * Router this rule belongs to
	 *
	 * @var   RouterView
	 * @since 3.4
	 */
	protected $router;

	/**
	 * Lookup array of the menu items
	 *
	 * @var   array
	 * @since 3.4
	 */
	protected $lookup = array();

	/**
	 * Class constructor.
	 *
	 * @param   RouterView  $router  Router this rule belongs to
	 *
	 * @since   3.4
	 */
	public function __construct(RouterView $router)
	{
		$this->router = $router;

		$this->buildLookup();
	}

	/**
	 * Finds the right Itemid for this query
	 *
	 * @param   array  &$query  The query array to process
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public function preprocess(&$query)
	{
		$active = $this->router->menu->getActive();

		/**
		 * If the active item id is not the same as the supplied item id or we have a supplied item id and no active
		 * menu item then we just use the supplied menu item and continue
		 */
		if (isset($query['Itemid']) && ($active === null || $query['Itemid'] != $active->id))
		{
			return;
		}

		// Get query language
		$language = isset($query['lang']) ? $query['lang'] : '*';

		if (!isset($this->lookup[$language]))
		{
			$this->buildLookup($language);
		}

		// Check if the active menu item matches the requested query
		if ($active !== null && isset($query['Itemid']))
		{
			// Check if active->query and supplied query are the same
			$match = true;

			foreach ($active->query as $k => $v)
			{
				if (isset($query[$k]) && $v !== $query[$k])
				{
					// Compare again without alias
					if (is_string($v) && $v == current(explode(':', $query[$k], 2)))
					{
						continue;
					}

					$match = false;
					break;
				}
			}

			if ($match)
			{
				// Just use the supplied menu item
				return;
			}
		}

		$needles = $this->router->getPath($query);

		$layout = isset($query['layout']) && $query['layout'] !== 'default' ? ':' . $query['layout'] : '';

		if ($needles)
		{
			foreach ($needles as $view => $ids)
			{
				$viewLayouts = array();

				if ($layout)
				{
					$viewLayouts[] = $view . $layout;
				}

				$viewLayouts[] = $view;
				$viewLayouts[] = $view . ':default';

				foreach($viewLayouts as $viewLayout)
				{
					if (isset($this->lookup[$language][$viewLayout]))
					{
						if (is_bool($ids))
						{
							$query['Itemid'] = $this->lookup[$language][$viewLayout];
	
							return;
						}

						foreach ($ids as $id => $segment)
						{
							if (isset($this->lookup[$language][$viewLayout][(int) $id]))
							{
								$query['Itemid'] = $this->lookup[$language][$viewLayout][(int) $id];
	
								return;
							}
						}
					}
				}
			}
		}

		// Check if the active menuitem matches the requested language
		if ($active && $active->component === 'com_' . $this->router->getName()
			&& ($language === '*' || in_array($active->language, array('*', $language)) || !\JLanguageMultilang::isEnabled()))
		{
			$query['Itemid'] = $active->id;
			return;
		}

		// If not found, return language specific home link
		$default = $this->router->menu->getDefault($language);

		if (!empty($default->id))
		{
			$query['Itemid'] = $default->id;
		}
	}

	/**
	 * Method to build the lookup array
	 *
	 * @param   string  $language  The language that the lookup should be built up for
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	protected function buildLookup($language = '*')
	{
		// Prepare the reverse lookup array.
		if (!isset($this->lookup[$language]))
		{
			$this->lookup[$language] = array();

			$component  = ComponentHelper::getComponent('com_' . $this->router->getName());
			$views = $this->router->getViews();

			$attributes = array('component_id');
			$values     = array((int) $component->id);

			$attributes[] = 'language';
			$values[]     = array($language, '*');

			$items = $this->router->menu->getItems($attributes, $values);

			foreach ($items as $item)
			{
				if (isset($item->query['view'], $views[$item->query['view']]))
				{
					$view = $item->query['view'];

					$layout = isset($item->query['layout']) && $item->query['layout'] !== 'default' ? ':' . $item->query['layout'] : '';

					if ($views[$view]->key)
					{
						if (!isset($this->lookup[$language][$view . $layout]))
						{
							$this->lookup[$language][$view . $layout] = array();
						}

						if (!isset($this->lookup[$language][$view]))
						{
							$this->lookup[$language][$view] = array();
						}

						// If menuitem has no key set, we assume 0.
						if (!isset($item->query[$views[$view]->key]))
						{
							$item->query[$views[$view]->key] = 0;
						}

						/**
						 * Here it will become a bit tricky
						 * language != * can override existing entries
						 * language == * cannot override existing entries
						 */
						if (!isset($this->lookup[$language][$view . $layout][$item->query[$views[$view]->key]]) || $item->language !== '*')
						{
							$this->lookup[$language][$view . $layout][$item->query[$views[$view]->key]] = $item->id;

							// Also if it specifies layout, then also add it as last choice fallback
							if ($layout)
							{
								$this->lookup[$language][$view . ':default'][$item->query[$views[$view]->key]] = $item->id;
							}
						}
					}
					else
					{
						/**
						 * Here it will become a bit tricky
						 * language != * can override existing entries
						 * language == * cannot override existing entries
						 */
						if (!isset($this->lookup[$language][$view . $layout]) || $item->language !== '*')
						{
							$this->lookup[$language][$view . $layout] = $item->id;

							// Also if it specifies layout, then also add it as last choice fallback
							if ($layout)
							{
								$this->lookup[$language][$view . ':default'] = $item->id;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Dummymethod to fullfill the interface requirements
	 *
	 * @param   array  &$segments  The URL segments to parse
	 * @param   array  &$vars      The vars that result from the segments
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @codeCoverageIgnore
	 */
	public function parse(&$segments, &$vars)
	{
	}

	/**
	 * Dummymethod to fullfill the interface requirements
	 *
	 * @param   array  &$query     The vars that should be converted
	 * @param   array  &$segments  The URL segments to create
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @codeCoverageIgnore
	 */
	public function build(&$query, &$segments)
	{
	}
}
