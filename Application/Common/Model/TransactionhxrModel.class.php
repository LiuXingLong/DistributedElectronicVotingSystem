<?php
namespace Common\Model;

class TransactionhxrModel extends BaseModel
{
	protected function init() {
		$this->DB = M($this->table , 'transaction_hxr_', 'DB_TPB_'.$this->databases );
	}
}