<?php
namespace Common\Model;

class TransactionproModel extends BaseModel
{
	protected function init() {
		$this->DB = M($this->table , 'transaction_pro_', 'DB_TPB_'.$this->databases );
	}
}