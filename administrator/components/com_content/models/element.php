<?php
/**
 * @version $Id: frontpage.php 2874 2006-03-22 22:57:55Z webImagery $
 * @package Joomla
 * @subpackage Content
 * @copyright Copyright (C) 2005 - 2006 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.extension.component');
jimport( 'joomla.application.model');

/**
 * Content Component Article Model
 *
 * @author	Louis Landry <louis.landry@joomla.org>
 * @package Joomla
 * @subpackage Content
 * @since 1.5
 */
class JContentModelElement extends JModel
{
	/**
	 * Content data in category array
	 *
	 * @var array
	 */
	var $_list = null;

	var $_page = null;

	/**
	 * Method to get content article data for the frontpage
	 *
	 * @since 1.5
	 */
	function getList()
	{
		if (!empty($this->_list)) {
			return $this->_list;
		}

		// Initialize variables
		$app	= &$this->getApplication();
		$db		= &$this->getDBO();
		$filter	= null;

		// Get some variables from the request
		$sectionid			= JRequest::getVar( 'sectionid', -1, '', 'int' );
		$redirect			= $sectionid;
		$option				= JRequest::getVar( 'option' );
		$filter_order		= $app->getUserStateFromRequest("articleelement.filter_order", 'filter_order', '');
		$filter_order_Dir	= $app->getUserStateFromRequest("articleelement.filter_order_Dir", 'filter_order_Dir', '');
		$filter_state		= $app->getUserStateFromRequest("articleelement.filter_state", 'filter_state', '');
		$catid				= $app->getUserStateFromRequest("articleelement.catid", 'catid', 0);
		$filter_authorid	= $app->getUserStateFromRequest("articleelement.filter_authorid", 'filter_authorid', 0);
		$filter_sectionid	= $app->getUserStateFromRequest("articleelement.filter_sectionid", 'filter_sectionid', -1);
		$limit				= $app->getUserStateFromRequest('limit', 'limit', $app->getCfg('list_limit'));
		$limitstart			= $app->getUserStateFromRequest("articleelement.limitstart", 'limitstart', 0);
		$search				= $app->getUserStateFromRequest("articleelement.search", 'search', '');
		$search				= $db->getEscaped(trim(JString::strtolower($search)));


		//$where[] = "c.state >= 0";
		$where[] = "c.state != -2";

		if (!$filter_order) {
			$filter_order = 'section_name';
		}
		$order = "\n ORDER BY $filter_order $filter_order_Dir, section_name, cc.name, c.ordering";
		$all = 1;

		if ($filter_sectionid >= 0) {
			$filter = "\n WHERE cc.section = $filter_sectionid";
		}
		$section->title = 'All Content Items';
		$section->id = 0;

		/*
		 * Add the filter specific information to the where clause
		 */
		// Section filter
		if ($filter_sectionid >= 0) {
			$where[] = "c.sectionid = $filter_sectionid";
		}
		// Category filter
		if ($catid > 0) {
			$where[] = "c.catid = $catid";
		}
		// Author filter
		if ($filter_authorid > 0) {
			$where[] = "c.created_by = $filter_authorid";
		}
		// Content state filter
		if ($filter_state) {
			if ($filter_state == 'P') {
				$where[] = "c.state = 1";
			} else {
				if ($filter_state == 'U') {
					$where[] = "c.state = 0";
				} else if ($filter_state == 'A') {
					$where[] = "c.state = -1";
				} else {
					$where[] = "c.state != -2";
				}
			}
		}
		// Keyword filter
		if ($search) {
			$where[] = "LOWER( c.title ) LIKE '%$search%'";
		}

		// Build the where clause of the content record query
		$where = (count($where) ? "\n WHERE ".implode(' AND ', $where) : '');

		// Get the total number of records
		$query = "SELECT COUNT(*)" .
				"\n FROM #__content AS c" .
				"\n LEFT JOIN #__categories AS cc ON cc.id = c.catid" .
				"\n LEFT JOIN #__sections AS s ON s.id = c.sectionid" .
				$where;
		$db->setQuery($query);
		$total = $db->loadResult();

		// Create the pagination object
		jimport('joomla.presentation.pagination');
		$this->_page = new JPagination($total, $limitstart, $limit);

		// Get the content items
		$query = "SELECT c.*, g.name AS groupname, cc.name, u.name AS editor, f.content_id AS frontpage, s.title AS section_name, v.name AS author" .
				"\n FROM #__content AS c" .
				"\n LEFT JOIN #__categories AS cc ON cc.id = c.catid" .
				"\n LEFT JOIN #__sections AS s ON s.id = c.sectionid" .
				"\n LEFT JOIN #__groups AS g ON g.id = c.access" .
				"\n LEFT JOIN #__users AS u ON u.id = c.checked_out" .
				"\n LEFT JOIN #__users AS v ON v.id = c.created_by" .
				"\n LEFT JOIN #__content_frontpage AS f ON f.content_id = c.id" .
				$where .
				$order;
		$db->setQuery($query, $this->_page->limitstart, $this->_page->limit);
		$this->_list = $db->loadObjectList();

		// If there is a db query error, throw a HTTP 500 and exit
		if ($db->getErrorNum()) {
			JError::raiseError( 500, $db->stderr() );
			return false;
		}

		return $this->_list;
	}

	function getPagination()
	{
		if (is_null($this->_list) || is_null($this->_page)) {
			$this->getList();
		}
		return $this->_page;
	}
}
?>