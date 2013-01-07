<?php
/**
 * Salesforce DataSource
 *
 * This DataSource is used to communicate with Salesforce CRM. It utilizes some CakePHP methods to construct
 * SOQL queries.
 *
 * @package default
 * @author Paul Marshall <http://www.protelligence.com>
 **/
App::uses('ApisSource', 'Apis.Model/Datasource');
App::uses('DboSource', 'Model/Datasource');
class Salesforce extends ApisSource {

/**
 * The description of this data source
 *
 * @var string
 */
	public $description = 'Salesforce Api DataSource';
/**
 * Holds the datasource configuration
 *
 * @var array
 */
	public $config = array();
/**
 * Holds a configuration map
 *
 * @var array
 */
	public $map = array();
/**
 * API options
 * @var array
 */
	public $options = array(
		'format'    => 'json',
		'ps'		=> '&', // param separator
		'kvs'		=> '=', // key-value separator
		'response_type' => 'code',
	);
	/**
	 * Start quote
	 *
	 * @var string
	 */
	public $startQuote = '"';

	/**
	 * End quote
	 *
	 * @var string
	 */
	public $endQuote = '"';
	/**
	 * The set of valid SQL operations usable in a WHERE statement
	 *
	 * @var array
	 */
	protected $_sqlOps = array('like', 'ilike', 'or', 'not', 'in', 'between', 'regexp', 'similar to');

	protected $_connection;

	/**
	 * Caches result from query parsing operations.  Cached results for both DboSource::name() and
	 * DboSource::conditions() will be stored here.  Method caching uses `md5()`. If you have
	 * problems with collisions, set DboSource::$cacheMethods to false.
	 *
	 * @var array
	 */
	public static $methodCache = array();

	/**
	 * Whether or not to cache the results of DboSource::name() and DboSource::conditions()
	 * into the memory cache.  Set to false to disable the use of the memory cache.
	 *
	 * @var boolean.
	 */
	public $cacheMethods = true;

/**
 * Loads HttpSocket class
 *
 * @param array $config
 * @param HttpSocket $Http
 */
	public function __construct($config, $Http = null) {
	            $this->User = ClassRegistry::init('User');
	    	    parent::__construct($config);
	}

	public function connect() {
	    $this->_connection = new PDO();
	}
/**
 * Just-In-Time callback for any last-minute request modifications
 *
 * @param object $model
 * @param array $request
 * @return array $request
 */
	public function beforeRequest(&$model, $request) {
	    //$this->log($model->name);
	    //$this->log($request);
       // $request['uri']['query']['response_type'] = $this->options['response_type'];
       //$request['uri']['query']['q'] = 'SELECT Id,Name,Email,MailingStreet,MailingCity,MailingState,MailingPostalCode FROM Contact';
       //$accessToken = SessionComponent::read('salesforce_access_token');
       //$request['header']['Authorization'] = 'OAuth ' . $accessToken;
       $request['auth']['method'] = 'OAuthV2';
	    return $request;
	}

	/**
	 * Uses standard find conditions. Use find('all', $params). Since you cannot pull specific fields,
	 * we will instead use 'fields' to specify what table to pull from.
	 *
	 * @param string $model The model being read.
	 * @param string $queryData An array of query data used to find the data you want
	 * @return mixed
	 * @access public
	 */
	public function read(&$model, $queryData = array()) {
	    //debug($model);
	    //debug($queryData);
	    if (!isset($model->request)) {
	        $model->request = array();
	    }
	    $model->request = array_merge(array('method' => 'GET'), $model->request);
	    if (!isset($queryData['conditions'])) {
	        $queryData['conditions'] = array();
	    }
	    if (empty($model->request['uri']['path']) && !empty($queryData['path'])) {
	        $model->request['uri']['path'] = $queryData['path'];
	        //$model->request['uri']['query'] = $queryData['conditions'];
	    } elseif (!empty($this->map['read']) && (is_string($queryData['fields']) || !empty($queryData['section']))) {
	        if (!empty($queryData['section'])) {
	            $section = $queryData['section'];
	        } else {
	            $section = $queryData['fields'];
	        }
	       // debug($section);
	        $scan = $this->scanMap($model, 'read', $model->name, array_keys($queryData['conditions']));
	        //debug($scan);
	        $model->request['uri']['path'] = $scan[0];
	        $model->request['uri']['query'] = array();
	        $usedConditions = array_intersect(array_keys($queryData['conditions']), array_merge($scan[1], $scan[2]));
	        debug($usedConditions);
	        foreach ($usedConditions as $condition) {
	            $model->request['uri']['query'][$condition] = $queryData['conditions'][$condition];
	        }
	    }
	    //$scan = $this->scanMap($model, 'read', $model->table, array_keys($queryData['conditions']));
	    //debug($scan);
	    $model->request['uri']['path'] = $queryData['path'];
	    //debug($model);
	    //debug($queryData['conditions']);
	    $Dbo = new DboSource(null, false);
	    $soql = $this->renderStatement('select', array(
	            'conditions' => $this->conditions($queryData['conditions'], true, true, $model),
			'alias' => null,
            'fields' => implode(', ', $queryData['fields']),
	            'table' => $model->table,
	            'joins' => null,
	            'order' => DboSource::order($queryData['order'], 'ASC', $model),
	            'limit' => DboSource::limit($queryData['limit'], $queryData['offset']),
	            'group' => DboSource::group($queryData['group'], $model)
	    ));
	     $soql = preg_replace('!\s+!', ' ', $soql);
	    $model->request['uri']['query']['q'] = $soql;
	    return $this->request($model);
	}
/**
 * Creates a WHERE clause by parsing given conditions data.  If an array or string
 * conditions are provided those conditions will be parsed and quoted.  If a boolean
 * is given it will be integer cast as condition.  Null will return 1 = 1.
 *
 * Results of this method are stored in a memory cache.  This improves performance, but
 * because the method uses a hashing algorithm it can have collisions.
 * Setting DboSource::$cacheMethods to false will disable the memory cache.
 *
 * @param mixed $conditions Array or string of conditions, or any value.
 * @param boolean $quoteValues If true, values should be quoted
 * @param boolean $where If true, "WHERE " will be prepended to the return value
 * @param Model $model A reference to the Model instance making the query
 * @return string SQL fragment
 */
	public function conditions($conditions, $quoteValues = true, $where = true, $model = null) {
		$clause = $out = '';

		if ($where) {
			$clause = ' WHERE ';
		}

		if (is_array($conditions) && !empty($conditions)) {
			$out = $this->conditionKeysToString($conditions, $quoteValues, $model);

			if (empty($out)) {
				return $clause . ' 1 = 1';
			}
			return $clause . implode(' AND ', $out);
		}
		if (is_bool($conditions)) {
			return $clause . (int)$conditions . ' = 1';
		}

		if (empty($conditions) || trim($conditions) === '') {
			return $clause . '1 = 1';
		}
		$clauses = '/^WHERE\\x20|^GROUP\\x20BY\\x20|^HAVING\\x20|^ORDER\\x20BY\\x20/i';

		if (preg_match($clauses, $conditions, $match)) {
			$clause = '';
		}
		//$conditions = $this->_quoteFields($conditions);
		return $clause . $conditions;
	}

/**
 * Creates a WHERE clause by parsing given conditions array.  Used by DboSource::conditions().
 *
 * @param array $conditions Array or string of conditions
 * @param boolean $quoteValues If true, values should be quoted
 * @param Model $model A reference to the Model instance making the query
 * @return string SQL fragment
 */
	public function conditionKeysToString($conditions, $quoteValues = true, $model = null) {
		$out = array();
		$data = $columnType = null;
		$bool = array('and', 'or', 'not', 'and not', 'or not', 'xor', '||', '&&');

		foreach ($conditions as $key => $value) {
			$join = ' AND ';
			$not = null;

			if (is_array($value)) {
				$valueInsert = (
					!empty($value) &&
					(substr_count($key, '?') === count($value) || substr_count($key, ':') === count($value))
				);
			}
			if (is_numeric($key) && empty($value)) {
				continue;
			} elseif (is_numeric($key) && is_string($value)) {
				$out[] = $not . $value;
			} elseif ((is_numeric($key) && is_array($value)) || in_array(strtolower(trim($key)), $bool)) {
				if (in_array(strtolower(trim($key)), $bool)) {
					$join = ' ' . strtoupper($key) . ' ';
				} else {
					$key = $join;
				}
				$value = $this->conditionKeysToString($value, $quoteValues, $model);

				if (strpos($join, 'NOT') !== false) {
					if (strtoupper(trim($key)) === 'NOT') {
						$key = 'AND ' . trim($key);
					}
					$not = 'NOT ';
				}

				if (empty($value[1])) {
					if ($not) {
						$out[] = $not . '(' . $value[0] . ')';
					} else {
						$out[] = $value[0];
					}
				} else {
					$out[] = '(' . $not . '(' . implode(') ' . strtoupper($key) . ' (', $value) . '))';
				}
			} else {
				if (is_object($value) && isset($value->type)) {
					if ($value->type === 'identifier') {
						$data .= $this->name($key) . ' = ' . $this->name($value->value);
					} elseif ($value->type === 'expression') {
						if (is_numeric($key)) {
							$data .= $value->value;
						} else {
							$data .= $this->name($key) . ' = ' . $value->value;
						}
					}
				} elseif (is_array($value) && !empty($value) && !$valueInsert) {
					$keys = array_keys($value);
					if ($keys === array_values($keys)) {
						$count = count($value);
						if ($count === 1 && !preg_match("/\s+NOT$/", $key)) {
							$data = $key . ' = (';
						} else {
							$data = $key . ' IN (';
						}
						if ($quoteValues) {
							if (is_object($model)) {
								$columnType = $model->getColumnType($key);
							}
							$data .= implode(', ', $this->User->getDataSource()->value($value, $columnType));
						}
						$data .= ')';
					} else {
						$ret = $this->conditionKeysToString($value, $quoteValues, $model);
						if (count($ret) > 1) {
							$data = '(' . implode(') AND (', $ret) . ')';
						} elseif (isset($ret[0])) {
							$data = $ret[0];
						}
					}
				} elseif (is_numeric($key) && !empty($value)) {
					$data = $value;
				} else {
					$data = $this->_parseKey($model, trim($key), $value);
				}

				if ($data != null) {
					$out[] = $data;
					$data = null;
				}
			}
		}
		return $out;
	}


/**
 * Extracts a Model.field identifier and an SQL condition operator from a string, formats
 * and inserts values, and composes them into an SQL snippet.
 *
 * @param Model $model Model object initiating the query
 * @param string $key An SQL key snippet containing a field and optional SQL operator
 * @param mixed $value The value(s) to be inserted in the string
 * @return string
 */
	protected function _parseKey($model, $key, $value) {
		$operatorMatch = '/^(((' . implode(')|(', $this->_sqlOps);
		$operatorMatch .= ')\\x20?)|<[>=]?(?![^>]+>)\\x20?|[>=!]{1,3}(?!<)\\x20?)/is';
		$bound = (strpos($key, '?') !== false || (is_array($value) && strpos($key, ':') !== false));

		if (strpos($key, ' ') === false) {
			$operator = '=';
		} else {
			list($key, $operator) = explode(' ', trim($key), 2);

			if (!preg_match($operatorMatch, trim($operator)) && strpos($operator, ' ') !== false) {
				$key = $key . ' ' . $operator;
				$split = strrpos($key, ' ');
				$operator = substr($key, $split);
				$key = substr($key, 0, $split);
			}
		}
		$virtual = false;
		if (is_object($model) && $model->isVirtualField($key)) {
			$key = $model->getVirtualField($key);
			$virtual = true;
		}

		$type = is_object($model) ? $model->getColumnType($key) : null;
		$null = $value === null || (is_array($value) && empty($value));

		if (strtolower($operator) === 'not') {
			$data = $this->conditionKeysToString(
				array($operator => array($key => $value)), true, $model
			);
			return $data[0];
		}

		$value = $this->User->getDataSource()->value($value, $type);

		if (!$virtual && $key !== '?') {
			$isKey = (strpos($key, '(') !== false || strpos($key, ')') !== false);
			$key = $isKey ? $key : $this->name($key);
		}

		if ($bound) {
			return String::insert($key . ' ' . trim($operator), $value);
		}

		if (!preg_match($operatorMatch, trim($operator))) {
			$operator .= ' =';
		}
		$operator = trim($operator);

		if (is_array($value)) {
			$value = implode(', ', $value);

			switch ($operator) {
				case '=':
					$operator = 'IN';
				break;
				case '!=':
				case '<>':
					$operator = 'NOT IN';
				break;
			}
			$value = "({$value})";
		} elseif ($null || $value === 'NULL') {
			switch ($operator) {
				case '=':
					$operator = 'IS';
				break;
				case '!=':
				case '<>':
					$operator = '!=';
				break;
			}
		}
		if ($virtual) {
			return "({$key}) {$operator} {$value}";
		}
		return "{$key} {$operator} {$value}";
	}

	/**
	 * Returns a quoted name of $data for use in an SQL statement.
	 * Strips fields out of SQL functions before quoting.
	 *
	 * Results of this method are stored in a memory cache.  This improves performance, but
	 * because the method uses a hashing algorithm it can have collisions.
	 * Setting DboSource::$cacheMethods to false will disable the memory cache.
	 *
	 * @param mixed $data Either a string with a column to quote. An array of columns to quote or an
	 *   object from DboSource::expression() or DboSource::identifier()
	 * @return string SQL field
	 */
	public function name($data) {
	    if (is_object($data) && isset($data->type)) {
	        return $data->value;
	    }
	    if ($data === '*') {
	        return '*';
	    }
	    if (is_array($data)) {
	        foreach ($data as $i => $dataItem) {
	            $data[$i] = $this->name($dataItem);
	        }
	        return $data;
	    }
	    $cacheKey = md5($data);
	    if ($return = $this->cacheMethod(__FUNCTION__, $cacheKey)) {
	        return $return;
	    }
	    $data = trim($data);
	    if (preg_match('/^[\w-]+(?:\.[^ \*]*)*$/', $data)) { // string, string.string
	        if (strpos($data, '.') === false) { // string
	            return $this->cacheMethod(__FUNCTION__, $cacheKey, $data);
	        }
	        $items = explode('.', $data);
	        return $this->cacheMethod(__FUNCTION__, $cacheKey,
	                implode('.', $items)
	        );
	    }
	    if (preg_match('/^[\w-]+\.\*$/', $data)) { // string.*
	        return $this->cacheMethod(__FUNCTION__, $cacheKey,
	                $data
	        );
	    }
	    if (preg_match('/^([\w-]+)\((.*)\)$/', $data, $matches)) { // Functions
	        return $this->cacheMethod(__FUNCTION__, $cacheKey,
	                $matches[1] . '(' . $this->name($matches[2]) . ')'
	        );
	    }
	    if (
	            preg_match('/^([\w-]+(\.[\w-]+|\(.*\))*)\s+' . preg_quote($this->alias) . '\s*([\w-]+)$/i', $data, $matches
	            )) {
	        return $this->cacheMethod(
	                __FUNCTION__, $cacheKey,
	                preg_replace(
	                        '/\s{2,}/', ' ', $this->name($matches[1]) . ' ' . $this->alias . ' ' . $this->name($matches[3])
	                )
	        );
	    }
	    if (preg_match('/^[\w-_\s]*[\w-_]+/', $data)) {
	        return $this->cacheMethod(__FUNCTION__, $cacheKey, $this->startQuote . $data . $this->endQuote);
	    }
	    return $this->cacheMethod(__FUNCTION__, $cacheKey, $data);
	}
	/**
	 * Cache a value into the methodCaches.  Will respect the value of DboSource::$cacheMethods.
	 * Will retrieve a value from the cache if $value is null.
	 *
	 * If caching is disabled and a write is attempted, the $value will be returned.
	 * A read will either return the value or null.
	 *
	 * @param string $method Name of the method being cached.
	 * @param string $key The key name for the cache operation.
	 * @param mixed $value The value to cache into memory.
	 * @return mixed Either null on failure, or the value if its set.
	 */
	public function cacheMethod($method, $key, $value = null) {
	    if ($this->cacheMethods === false) {
	        return $value;
	    }
	    if (empty(self::$methodCache)) {
	        self::$methodCache = Cache::read('method_cache', '_cake_core_');
	    }
	    if ($value === null) {
	        return (isset(self::$methodCache[$method][$key])) ? self::$methodCache[$method][$key] : null;
	    }
	    $this->_methodCacheChange = true;
	    return self::$methodCache[$method][$key] = $value;
	}

/**
 * Quotes Model.fields
 *
 * @param string $conditions
 * @return string or false if no match
 */
	protected function _quoteFields($conditions) {
		$start = $end = null;
		$original = $conditions;

		if (!empty($this->startQuote)) {
			$start = preg_quote($this->startQuote);
		}
		if (!empty($this->endQuote)) {
			$end = preg_quote($this->endQuote);
		}
		$conditions = str_replace(array($start, $end), '', $conditions);
		$conditions = preg_replace_callback(
			'/(?:[\'\"][^\'\"\\\]*(?:\\\.[^\'\"\\\]*)*[\'\"])|([a-z0-9\\-_' . $start . $end . ']*\\.[a-z0-9_\\-' . $start . $end . ']*)/i',
			array(&$this, '_quoteMatchedField'),
			$conditions
		);
		if ($conditions !== null) {
			return $conditions;
		}
		return $original;
	}	public function describe(Model $model) {
	    return $this->request($model);

	}

	/**
	 * Returns an array of Salesforce Objects.
	 * @todo This needs to be updated for Salesforce. Pulled from MySQL datasource
	 * @param mixed $data
	 * @return array Array of table names in the database
	 */
	public function listSources($data = null) {
	    $cache = parent::listSources();
	    if ($cache != null) {
	        return $cache;
	    }
	    $result = $this->_execute('SHOW TABLES FROM ' . $this->name($this->config['database']));

	    if (!$result) {
	        $result->closeCursor();
	        return array();
	    } else {
	        $tables = array();

	        while ($line = $result->fetch(PDO::FETCH_NUM)) {
	            $tables[] = $line[0];
	        }

	        $result->closeCursor();
	        parent::listSources($tables);
	        return $tables;
	    }
	}

	/**
	 * Builds and generates an SQL statement from an array.	 Handles final clean-up before conversion.
	 *
	 * @todo This needs to be updated
	 * @param array $query An array defining an SQL query
	 * @param Model $model The model object which initiated the query
	 * @return string An executable SQL statement
	 * @see DboSource::renderStatement()
	 */
	public function buildStatement($query, $model) {
	    $query = array_merge($this->_queryDefaults, $query);
	    if (!empty($query['joins'])) {
	        $count = count($query['joins']);
	        for ($i = 0; $i < $count; $i++) {
	            if (is_array($query['joins'][$i])) {
	                $query['joins'][$i] = $this->buildJoinStatement($query['joins'][$i]);
	            }
	        }
	    }
	    return $this->renderStatement('select', array(
	            'conditions' => $this->conditions($query['conditions'], true, true, $model),
	            'fields' => implode(', ', $query['fields']),
	            'table' => $query['table'],
	            'alias' => $this->alias . $this->name($query['alias']),
	            'order' => $this->order($query['order'], 'ASC', $model),
	            'limit' => $this->limit($query['limit'], $query['offset']),
	            'joins' => implode(' ', $query['joins']),
	            'group' => $this->group($query['group'], $model)
	    ));
	}

	/**
	 * Renders a final SQL JOIN statement
	 *
	 * @param array $data
	 * @return string
	 */
	public function renderJoinStatement($data) {
	    extract($data);
	    return trim("{$type} JOIN {$table} {$alias} ON ({$conditions})");
	}

	/**
	 * Renders a final SQL statement by putting together the component parts in the correct order
	 *
	 * @param string $type type of query being run.  e.g select, create, update, delete, schema, alter.
	 * @param array $data Array of data to insert into the query.
	 * @return string Rendered SQL expression to be run.
	 */
	public function renderStatement($type, $data) {
	    extract($data);
	    $aliases = null;

	    switch (strtolower($type)) {
	        case 'select':
	            return "SELECT {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order} {$limit}";
	            case 'create':
	            return "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
	            case 'update':
	            if (!empty($alias)) {
	                $aliases = "{$this->alias}{$alias} {$joins} ";
	                }
	                return "UPDATE {$table} {$aliases}SET {$fields} {$conditions}";
	                case 'delete':
	                if (!empty($alias)) {
	                    $aliases = "{$this->alias}{$alias} {$joins} ";
	    }
	    return "DELETE {$alias} FROM {$table} {$aliases}{$conditions}";
	    case 'schema':
	    foreach (array('columns', 'indexes', 'tableParameters') as $var) {
	    if (is_array(${
	    $var})) {
	    ${
	    $var} = "\t" . join(",\n\t", array_filter(${
	    $var}));
	    } else {
	    ${
	    $var} = '';
	    }
	    }
	    if (trim($indexes) !== '') {
	    $columns .= ',';
					}
					return "CREATE TABLE {$table} (\n{$columns}{$indexes}) {$tableParameters};";
				case 'alter':
					return;
			}
		}

/**
 * Stores the queryData so that the tokens can be substituted just before requesting
 *
 * @param string $model
 * @param string $queryData
 * @return mixed $data
 */
/*	public function read(&$model, $queryData = array()) {
	    debug($queryData);
		 $this->tokens = $queryData['conditions']; // Swap out tokens for passed conditions
		return parent::read($model, $queryData);
	}*/
}