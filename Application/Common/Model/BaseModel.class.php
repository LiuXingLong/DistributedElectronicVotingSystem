<?php
namespace Common\Model;

abstract class BaseModel
{
	public       $DB;
	public       $Trans;
	protected    $table;
	protected    $databases;
	public function __construct( $id )
	{			
		$this->databases = substr($id,10,4);
		$this->table = substr($id,14,2);
		$this->init();
	}
	abstract protected function init();
}