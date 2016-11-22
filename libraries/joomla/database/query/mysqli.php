<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Database
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Query Building Class.
 *
 * @since  11.1
 */
class JDatabaseQueryMysqli extends JDatabaseQuery implements JDatabaseQueryLimitable
{
	/**
	 * @var    integer  The offset for the result set.
	 * @since  12.1
	 */
	protected $offset;

	/**
	 * @var    integer  The limit for the result set.
	 * @since  12.1
	 */
	protected $limit;

	/**
	 * Magic function to convert the query to a string.
	 *
	 * @return  string  The completed query.
	 *
	 * @since   11.1
	 */
	public function __toString()
	{
		if ($this->windowOrder)
		{
			$tmpOrder    = $this->order;
			$this->order = $this->windowOrder;
			$query       = parent::__toString();
			$this->order = $tmpOrder;

			if ($this->order)
			{
				return PHP_EOL . "SELECT * FROM ( $query ) AS " . $this->quoteName('window') . (string) $this->order;
			}

			return $query;
		}

		return parent::__toString();
	}

	/**
	 * Clear data from the query or a specific clause of the query.
	 *
	 * @param   string  $clause  Optionally, the name of the clause to clear, or nothing to clear the whole query.
	 *
	 * @return  JDatabaseQuery  Returns this object to allow chaining.
	 *
	 * @since   11.1
	 */
	public function clear($clause = null)
	{
		$tmpSelect = $this->select;

		parent::clear($clause);

		if ($tmpSelect !== $this->select)
		{
			$this->windowOrder = null;
		}

		return $this;
	}

	/**
	 * Method to modify a query already in string format with the needed
	 * additions to make the query limited to a particular number of
	 * results, or start at a particular offset.
	 *
	 * @param   string   $query   The query in string format
	 * @param   integer  $limit   The limit for the result set
	 * @param   integer  $offset  The offset for the result set
	 *
	 * @return string
	 *
	 * @since 12.1
	 */
	public function processLimit($query, $limit, $offset = 0)
	{
		if ($limit > 0 && $offset > 0)
		{
			$query .= ' LIMIT ' . $offset . ', ' . $limit;
		}
		elseif ($limit > 0)
		{
			$query .= ' LIMIT ' . $limit;
		}

		return $query;
	}

	/**
	 * Concatenates an array of column names or values.
	 *
	 * @param   array   $values     An array of values to concatenate.
	 * @param   string  $separator  As separator to place between each value.
	 *
	 * @return  string  The concatenated values.
	 *
	 * @since   11.1
	 */
	public function concatenate($values, $separator = null)
	{
		if ($separator)
		{
			$concat_string = 'CONCAT_WS(' . $this->quote($separator);

			foreach ($values as $value)
			{
				$concat_string .= ', ' . $value;
			}

			return $concat_string . ')';
		}
		else
		{
			return 'CONCAT(' . implode(',', $values) . ')';
		}
	}

	/**
	 * Sets the offset and limit for the result set, if the database driver supports it.
	 *
	 * Usage:
	 * $query->setLimit(100, 0); (retrieve 100 rows, starting at first record)
	 * $query->setLimit(50, 50); (retrieve 50 rows, starting at 50th record)
	 *
	 * @param   integer  $limit   The limit for the result set
	 * @param   integer  $offset  The offset for the result set
	 *
	 * @return  JDatabaseQuery  Returns this object to allow chaining.
	 *
	 * @since   12.1
	 */
	public function setLimit($limit = 0, $offset = 0)
	{
		$this->limit  = (int) $limit;
		$this->offset = (int) $offset;

		return $this;
	}

	/**
	 * Return correct regexp operator for mysqli.
	 *
	 * Ensure that the regexp operator is mysqli compatible.
	 *
	 * Usage:
	 * $query->where('field ' . $query->regexp($search));
	 *
	 * @param   string  $value  The regex pattern.
	 *
	 * @return  string  Returns the regex operator.
	 *
	 * @since   11.3
	 */
	public function regexp($value)
	{
		return ' REGEXP ' . $value;
	}

	/**
	 * Return correct rand() function for Mysql.
	 *
	 * Ensure that the rand() function is Mysql compatible.
	 * 
	 * Usage:
	 * $query->Rand();
	 * 
	 * @return  string  The correct rand function.
	 *
	 * @since   3.5
	 */
	public function Rand()
	{
		return ' RAND() ';
	}

	/**
	 * Return number of the current row, starting from 1
	 *
	 * @param   mixed  $columns  A string or array of ordering columns.
	 * @param   mixed  $as       The AS query part associated to generated column.
	 *                           If is null there will not be any AS part for generated column.
	 *
	 * @return  JDatabaseQuery  Returns this object to allow chaining.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  RuntimeException
	 */
	public function windowRowNumber($columns, $as = null)
	{
		if ($this->windowOrder)
		{
			throw new RuntimeException("Method 'windowRowNumber' can be called only once per instance.");
		}

		// Special element to emulate ROW_NUMBER OVER (ORDER BY ...)
		$this->windowOrder = new JDatabaseQueryElement('ORDER BY', $columns);

		// Add column to count row number
		$column = '(SELECT @a := @a + 1 FROM (SELECT @a := 0) AS ' . $this->quoteName('row_init') . ')';
		$this->select($column . ($as === null ? '' : ' AS ' . $as));

		return $this;
	}
}
