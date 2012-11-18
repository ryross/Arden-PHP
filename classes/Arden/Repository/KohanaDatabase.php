<?php

class Arden_Repository_KohanaDatabase
{
	protected $_model_class;
	protected $_table_name;

	private $_qb_select;
	private $_qb_insert;
	private $_qb_update;
	private $_qb_delete;

	protected $rules;

	/**
	 * @return Database_Query_Builder_Select
	 */
	protected function new_select()
	{
		return clone $this->_qb_select;
	}

	/**
	 * @return Database_Query_Builder_Insert
	 */
	protected function new_insert()
	{
		return clone $this->_qb_insert;
	}

	/**
	 * @return Database_Query_Builder_Update
	 */
	protected function new_update()
	{
		return clone $this->_qb_update;
	}

	/**
	 * @return Database_Query_Builder_Delete
	 */
	protected function new_delete()
	{
		return clone $this->_qb_delete;
	}

	protected function as_array($repository_results)
	{
		$results = [];

		foreach ($repository_results as $result)
		{
			$results[] = $result;
		}

		return $results;
	}

	public function __construct($database, $qb_select, $qb_insert, $qb_update, $qb_delete, $model_class = NULL, $table_name = NULL)
	{
		$this->_database = $database;

		if ($table_name)
		{
			$this->_table_name = $table_name;
		}

		if ($model_class)
		{
			$this->_model_class = $model_class;
		}

		$this->_qb_select = $qb_select;
		$this->_qb_insert = $qb_insert;
		$this->_qb_update = $qb_update;
		$this->_qb_delete = $qb_delete;

		$this->initialize();
	}

	public function load_object(array $parameters, $select = NULL)
	{
		if ($select === NULL)
		{
			$select = $this->new_select();
		}
		$select->from($this->_table_name);
		$select->as_object($this->_model_class);
		foreach ($parameters as $column => $value)
		{
			$select->where($column, '=', $value);
		}

		return $select->execute($this->_database)->current();
	}

	public function load_set(array $parameters, $select = NULL)
	{
		if ($select === NULL)
		{
			$select = $this->new_select();
		}
		$select->from($this->_table_name);
		$select->as_object($this->_model_class);
		foreach($parameters as $parameter_set)
		{
			foreach ($parameter_set as $column => $value)
			{
				$select->where($column, '=', $value);
			}
		}

		$results = [];
		foreach ($select->execute($this->_database) as $model)
		{
			$results[] = $model;
		}

		return $results;
	}

	public function create($object, $insert = NULL)
	{
		if ($object->id)
		{
			throw new Arden_InvalidObjectException('Cannot create a loaded object');
		}

		$reflection = new ReflectionClass($object);
		$properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
		$columns = [];
		$values = [];
		foreach ($properties as $p)
		{
			$columns[] = $p->getName();
			$values[] = $object->{$p->getName()};
		}

		if ($insert === NULL)
		{
			$insert = $this->new_insert();
		}
		$object->id = $insert->table($this->_table_name)->columns($columns)->values($values)->execute($this->_database)[0];

		return $object;
	}

	public function update($object, $update = NULL)
	{
		if ( ! $object->id)
		{
			throw new Arden_InvalidObjectException('Cannot update a non-loaded object');
		}

		$reflection = new ReflectionClass($object);
		$properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
		$set = [];
		foreach ($properties as $p)
		{
			$set[$p->getName()] = $object->{$p->getName()};
		}

		if ($update === NULL)
		{
			$update = $this->new_update();
		}
		$update->table($this->_table_name)->where('id', '=', $object->id)->set($set)->execute($this->_database);

		return $object;
	}

	public function delete($object, $delete = NULL)
	{
		if ( ! $object->id)
		{
			throw new Arden_InvalidObjectException('Cannot delete a non-loaded object');
		}

		if ($delete === NULL)
		{
			$delete = $this->new_delete();
		}
		return (bool) $delete->table($this->_table_name)->where('id', '=', $object->id)->execute($this->_database);
	}

	/**
	 * Override to provide extra initialization such as $rules.
	 */
	protected function initialize(){}

	/**
	 * @param $data array to be validated.
	 * @return Validation
	 */
	public function validation($data)
	{
		$validation = Validation::factory($data);

		foreach($this->rules as $field => $field_rules)
		{
			$validation->rules($field, $field_rules);
		}

		return $validation;
	}

}
